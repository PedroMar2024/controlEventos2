<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class EventoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
{
    $user = auth()->user();

    if ($user->hasRole('superadmin')) {
        $eventos = Evento::orderBy('fecha_evento','desc')->paginate(15);
    } else {
        $personaId = optional($user->persona)->id;

        // SOLO eventos donde el usuario está vinculado (admin o subadmin). Sin “publico”.
        $eventos = Evento::whereHas('personas', function ($q) use ($personaId) {
                $q->where('personas.id', $personaId)
                  ->whereIn('event_persona_roles.role', ['admin','subadmin']);
            })
            ->orderBy('fecha_evento','desc')
            ->paginate(15);
    }

    return view('eventos.index', compact('eventos'));
}

    public function create()
    {
        // Superadmin pasa siempre
        return view('eventos.create');
    }

    public function store(Request $request)
{
    Log::debug('Eventos@store payload', $request->except('_token'));

    try {
        $data = $request->validate([
            'nombre'        => ['required','string','max:255'],
            'descripcion'   => ['nullable','string'],
            'fecha_evento'  => ['nullable','date'],
            'hora_inicio'   => ['nullable','date_format:H:i'],
            'hora_cierre'   => ['nullable','date_format:H:i'],
            'ubicacion'     => ['nullable','string','max:255'],
            'localidad'     => ['nullable','string','max:255'],
            'provincia'     => ['nullable','string','max:255'],
            // 'capacidad'   => ['nullable','integer','min:1'], // Derivada de tickets: QUITADA
            'estado'        => ['nullable','in:pendiente,aprobado,finalizado'],
            // 'precio_evento' => ['nullable','numeric','min:0'], // Deprecado: QUITADA
            'publico'       => ['nullable','boolean'],
            'reingreso'     => ['nullable','boolean'],

            // Admin por email
            'admin_email'   => ['required','email'],
            'admin_nombre'  => ['nullable','string','max:255'],
            'admin_dni'     => ['nullable','string','max:32'],

            // Tickets (máximo 5)
            'tickets'            => ['nullable','array','max:5'],
            'tickets.*.id'       => ['nullable','integer'],
            'tickets.*.nombre'   => ['nullable','string','max:100'],
            'tickets.*.precio'   => ['nullable','numeric','min:0'],
            'tickets.*.cupo'     => ['nullable','integer','min:0'],
            'tickets.*.activo'   => ['nullable','boolean'],
            'tickets.*._destroy' => ['nullable','boolean'],
        ]);

        // Persona admin por email
        $adminPersona = Persona::where('email', $request->string('admin_email'))->first();
        if (!$adminPersona) {
            $request->validate([
                'admin_nombre'  => ['required','string','max:255'],
                'admin_dni'     => ['required','string','max:32'],
            ]);

            $adminPersona = Persona::create([
                'email'  => (string) $request->string('admin_email'),
                'nombre' => (string) $request->string('admin_nombre'),
                'dni'    => (string) $request->string('admin_dni'),
            ]);
        }
            // Persona admin por email
            $adminPersona = Persona::where('email', $request->string('admin_email'))->first();
            if (!$adminPersona) {
                $request->validate([
                    'admin_nombre'  => ['required','string','max:255'],
                    'admin_dni'     => ['required','string','max:32'],
                ]);

                $adminPersona = Persona::create([
                    'email'  => (string) $request->string('admin_email'),
                    'nombre' => (string) $request->string('admin_nombre'),
                    'dni'    => (string) $request->string('admin_dni'),
                ]);
            }

            /* === Bloque insertar aquí: asegurar cuenta y enviar email === */
            $user = User::where('email', $adminPersona->email)->first();

            if (!$user) {
                // Crea el User con password aleatoria y lo vincula a Persona
                $user = User::create([
                    'name'       => $adminPersona->nombre ?: $adminPersona->email,
                    'email'      => $adminPersona->email,
                    'password'   => bcrypt(Str::random(32)),
                    'persona_id' => $adminPersona->id,
                ]);

                // Envía el email con el link de "establecer contraseña"
                $status = Password::sendResetLink(['email' => $user->email]);

                \Log::info('Onboarding admin evento: reset link enviado', [
                    'email'  => $user->email,
                    'status' => $status,
                ]);
            } else {
                // Asegura vínculo a Persona si faltara
                if (!$user->persona_id) {
                    $user->persona_id = $adminPersona->id;
                    $user->save();
                }
                // Si querés forzar reenvío del link siempre, descomenta:
                // Password::sendResetLink(['email' => $user->email]);
            }
            /* === Fin del bloque a insertar === */

            // Normalizar checkboxes y continuar con la creación del Evento…
            $data['publico']   = $request->boolean('publico');
            $data['reingreso'] = $request->boolean('reingreso');

            // ... resto de tu método store (crear $evento, guardar, pivot admin, tickets y capacidad)
        // Normalizar checkboxes
        $data['publico']   = $request->boolean('publico');
        $data['reingreso'] = $request->boolean('reingreso');

        // Crear evento
        $evento = new Evento();
        foreach ($evento->getFillable() as $col) {
            if (array_key_exists($col, $data)) {
                $evento->{$col} = $data[$col];
            }
        }
        // Forzar provincia con lo que llega (evita que quede un valor previo o vacío)
        $evento->provincia = $request->input('provincia');

        // Normaliza horas a H:i por si el browser envía con segundos
        $evento->hora_inicio = $request->filled('hora_inicio') ? substr($request->input('hora_inicio'), 0, 5) : null;
        $evento->hora_cierre = $request->filled('hora_cierre') ? substr($request->input('hora_cierre'), 0, 5) : null;

        $evento->admin_persona_id = $adminPersona->id;

        Log::info('STORE provincia', [
            'input' => $request->input('provincia'),
        ]);

        $evento->save();

        Log::info('STORE provincia AFTER', [
            'persisted' => $evento->provincia,
        ]);

        // Pivot rol admin
        if (method_exists($evento, 'personas')) {
            $evento->personas()->syncWithoutDetaching([
                $adminPersona->id => ['role' => 'admin']
            ]);
        }

        // Sincroniza tickets y deriva capacidad
        $this->syncTickets($evento, $request->input('tickets', []));
        $evento->capacidad = $this->computeCapacityFromTickets($evento);
        $evento->save();

        return redirect()->route('eventos.index')->with('success', 'Evento creado correctamente.');
    } catch (Throwable $e) {
        Log::error('Error en Eventos@store', [
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
        return back()->withInput()->withErrors(['app' => $e->getMessage()]);
    }
}

public function show(Evento $evento)
{
    $this->authorize('view', $evento);
    $evento->load('tickets', 'adminPersona');
    return view('eventos.show', compact('evento'));
}

    public function edit($id)
    {
        if (auth()->user()->hasRole('superadmin')) {
            // Saltá la autorización, va directo
            \Log::info("FORZADO controller: superadmin", [
                'user_id' => auth()->id(),
                'evento' => $evento->id,
            ]);
        } else {
            $this->authorize('update', $evento);
        }
        $evento = Evento::findOrFail($id);

        // Bypass para superadmin
        if (!auth()->user()->hasRole('superadmin')) {
            Gate::authorize('editar-evento', $evento);
        }

        return view('eventos.edit', compact('evento'));
    }

    public function update(Request $request, $id)
{   
    if (auth()->user()->hasRole('superadmin')) {
        // Saltá la autorización, va directo
        \Log::info("FORZADO controller: superadmin", [
            'user_id' => auth()->id(),
            'evento' => $evento->id,
        ]);
    } else {
        $this->authorize('update', $evento);
    }
    Log::debug('Eventos@update payload', $request->except('_token'));

    try {
        $evento = Evento::findOrFail($id);

        if (!auth()->user()->hasRole('superadmin')) {
            Gate::authorize('editar-evento', $evento);
        }

        $validated = $request->validate([
            'nombre'        => ['required','string','max:255'],
            'descripcion'   => ['nullable','string'],
            'fecha_evento'  => ['required','date'],
            'hora_inicio'   => ['nullable','date_format:H:i'],
            'hora_cierre'   => ['nullable','date_format:H:i'],
            'ubicacion'     => ['nullable','string','max:255'],
            'localidad'     => ['nullable','string','max:255'],
            'provincia'     => ['nullable','string','max:255'],
            // 'capacidad'   => ['nullable','integer','min:1'], // Derivada de tickets: QUITADA
            'estado'        => ['required','in:pendiente,aprobado,finalizado'],
            // 'precio_evento' => ['nullable','numeric','min:0'], // Deprecado: QUITADA
            'publico'       => ['boolean'],
            'reingreso'     => ['nullable','boolean'],

            // Tickets (máximo 5)
            'tickets'            => ['nullable','array','max:5'],
            'tickets.*.id'       => ['nullable','integer'],
            'tickets.*.nombre'   => ['nullable','string','max:100'],
            'tickets.*.precio'   => ['nullable','numeric','min:0'],
            'tickets.*.cupo'     => ['nullable','integer','min:0'],
            'tickets.*.activo'   => ['nullable','boolean'],
            'tickets.*._destroy' => ['nullable','boolean'],
        ]);

        $validated['publico']   = $request->boolean('publico');
        $validated['reingreso'] = $request->boolean('reingreso');

        foreach ($evento->getFillable() as $col) {
            if (array_key_exists($col, $validated)) {
                $evento->{$col} = $validated[$col];
            }
        }

        // Forzar provincia con lo que llega
        $before = $evento->provincia;
        $evento->provincia = $request->input('provincia');

        // Normaliza horas a H:i
        $evento->hora_inicio = $request->filled('hora_inicio') ? substr($request->input('hora_inicio'), 0, 5) : null;
        $evento->hora_cierre = $request->filled('hora_cierre') ? substr($request->input('hora_cierre'), 0, 5) : null;

        Log::info('UPDATE provincia', [
            'before' => $before,
            'input'  => $request->input('provincia'),
        ]);

        $evento->save();

        Log::info('UPDATE provincia AFTER', [
            'after' => $evento->provincia,
            'dirty' => $evento->getChanges(),
        ]);

        // Sincroniza tickets y deriva capacidad
        $this->syncTickets($evento, $request->input('tickets', []));
        $evento->capacidad = $this->computeCapacityFromTickets($evento);
        $evento->save();

        return redirect()->route('eventos.index')->with('success', 'Evento actualizado');
    } catch (Throwable $e) {
        Log::error('Error en Eventos@update', [
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
        return back()->withInput()->withErrors(['app' => $e->getMessage()]);
    }
}
    public function destroy($id)
    {
        $evento = Evento::findOrFail($id);

        if (!auth()->user()->hasRole('superadmin')) {
            Gate::authorize('eliminar-evento', $evento);
        }

        $evento->delete();
        return redirect()->route('eventos.index')->with('success', 'Evento eliminado');
    }

    // Opcionales si existen en tus rutas:
    public function aprobar(\App\Models\Evento $evento)
    {
        $this->authorize('approve', $evento);
        $evento->estado = 'aprobado';
        $evento->save();
        return redirect()->route('eventos.index')->with('success', 'Evento aprobado');
    }
    
    public function cancelar(\App\Models\Evento $evento)
    {
        $this->authorize('cancel', $evento);
        $evento->estado = 'pendiente';
        $evento->save();
        return redirect()->route('eventos.index')->with('success', 'Evento marcado como pendiente');
    }
    
    private function syncTickets(Evento $evento, array $tickets): void
{
    $rows = collect($tickets ?? [])
        ->filter(fn($t) => empty($t['_destroy']))
        ->filter(fn($t) => isset($t['nombre']) && trim($t['nombre']) !== '');

    // nombres duplicados en la misma petición no permitidos
    $names = $rows->map(fn($t) => strtolower(trim($t['nombre'])));
    if ($names->count() !== $names->unique()->count()) {
        abort(422, 'No se permiten nombres de entrada duplicados.');
    }

    foreach ($tickets ?? [] as $t) {
        $destroy = !empty($t['_destroy']);
        $id      = $t['id'] ?? null;

        if ($id && $destroy) {
            \App\Models\EventoTicket::where('evento_id', $evento->id)->where('id', $id)->delete();
            continue;
        }

        if ($id && !$destroy) {
            \App\Models\EventoTicket::where('evento_id', $evento->id)->where('id', $id)->update([
                'nombre' => $t['nombre'] ?? '',
                'precio' => isset($t['precio']) ? (float)$t['precio'] : 0,
                'cupo'   => isset($t['cupo']) ? (int)$t['cupo'] : null,
                'activo' => !empty($t['activo']),
            ]);
            continue;
        }

        if (!$id && !$destroy && isset($t['nombre']) && trim($t['nombre']) !== '') {
            \App\Models\EventoTicket::create([
                'evento_id' => $evento->id,
                'nombre'    => $t['nombre'],
                'precio'    => isset($t['precio']) ? (float)$t['precio'] : 0,
                'cupo'      => isset($t['cupo']) ? (int)$t['cupo'] : null,
                'activo'    => !empty($t['activo']),
            ]);
        }
    }
}

private function computeCapacityFromTickets(Evento $evento): int
{
    $evento->loadMissing('tickets');
    return (int) $evento->tickets
        ->where('activo', true)
        ->sum(function ($t) { return (int)($t->cupo ?? 0); });
}
}
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
        $eventos = \App\Models\Evento::with('personas') // CARGO LA LISTA DE PERSONAS/PERMISOS
            ->orderBy('fecha_evento','desc')
            ->paginate(15);
    } else {
        $personaId = optional($user->persona)->id;

        $eventos = \App\Models\Evento::whereHas('personas', function ($q) use ($personaId) {
                $q->where('personas.id', $personaId)
                  ->whereIn('event_persona_roles.role', ['admin','subadmin']);
            })
            ->with('personas') // Aca tambien CARGO la lista para poder chequear los permisos en la vista
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
        $user = auth()->user();
        $isSuperadmin = $user->hasRole('superadmin');

        // VALIDACIÓN SEGÚN ROL
        $rulesBase = [
            'nombre'        => ['required','string','max:255'],
            'descripcion'   => ['nullable','string'],
            'fecha_evento'  => ['nullable','date'],
            'hora_inicio'   => ['nullable','date_format:H:i'],
            'hora_cierre'   => ['nullable','date_format:H:i'],
            'ubicacion'     => ['nullable','string','max:255'],
            'localidad'     => ['nullable','string','max:255'],
            'provincia'     => ['nullable','string','max:255'],
            'estado'        => ['nullable','in:pendiente,aprobado,finalizado'],
            'publico'       => ['nullable','boolean'],
            'reingreso'     => ['nullable','boolean'],
            'tickets'            => ['nullable','array','max:5'],
            'tickets.*.id'       => ['nullable','integer'],
            'tickets.*.nombre'   => ['nullable','string','max:100'],
            'tickets.*.precio'   => ['nullable','numeric','min:0'],
            'tickets.*.cupo'     => ['nullable','integer','min:0'],
            'tickets.*.activo'   => ['nullable','boolean'],
            'tickets.*._destroy' => ['nullable','boolean'],
        ];

        if ($isSuperadmin) {
            $rules = array_merge($rulesBase, [
                'admin_email'  => ['required','email'],
                'admin_nombre' => ['nullable','string','max:255'],
                'admin_dni'    => ['nullable','string','max:32'],
            ]);
        } else {
            $rules = $rulesBase;
        }

        $data = $request->validate($rules);

        // === IDENTIFICAR Y OBTENER ADMIN/RESPONSABLE SEGÚN ROL ===
        if ($isSuperadmin) {
            // Persona admin por email
            $adminPersona = Persona::where('email', $request->string('admin_email'))->first();

            if (!$adminPersona) {
                $request->validate([
                    'admin_nombre' => ['required','string','max:255'],
                    'admin_dni'    => ['required','string','max:32'],
                ]);
                $adminPersona = Persona::create([
                    'email'  => (string) $request->string('admin_email'),
                    'nombre' => (string) $request->string('admin_nombre'),
                    'dni'    => (string) $request->string('admin_dni'),
                ]);
            }

            $adminUser = User::where('email', $adminPersona->email)->first();
            if (!$adminUser) {
                $adminUser = User::create([
                    'name'       => $adminPersona->nombre ?: $adminPersona->email,
                    'email'      => $adminPersona->email,
                    'password'   => bcrypt(Str::random(32)),
                    'persona_id' => $adminPersona->id,
                ]);
                Password::sendResetLink(['email' => $adminUser->email]);
            } else {
                if (!$adminUser->persona_id) {
                    $adminUser->persona_id = $adminPersona->id;
                    $adminUser->save();
                }
            }
            $adminUser->assignRole('admin_evento');
            $adminPersonaId = $adminPersona->id;
        } else {
            // Para admin_evento: el responsable es el logueado
            $adminUser = $user;
            $adminPersona = $user->persona;
            if (!$adminPersona) {
                // Si el usuario admin no tiene persona, creala (raro, pero seguro)
                $adminPersona = Persona::create([
                    'email'  => $user->email,
                    'nombre' => $user->name,
                    'dni'    => '', // Podés ajustarlo si tenés otro campo
                ]);
                $user->persona_id = $adminPersona->id;
                $user->save();
            }
            $adminPersonaId = $adminPersona->id;
        }

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
        $evento->provincia = $request->input('provincia');
        $evento->hora_inicio = $request->filled('hora_inicio') ? substr($request->input('hora_inicio'), 0, 5) : null;
        $evento->hora_cierre = $request->filled('hora_cierre') ? substr($request->input('hora_cierre'), 0, 5) : null;
        $evento->admin_persona_id = $adminPersonaId;
        if (!$user->hasRole('superadmin')) {
            $evento->estado = 'pendiente';
        }
        $evento->save();

        // Pivot rol admin
        if (method_exists($evento, 'personas')) {
            $evento->personas()->syncWithoutDetaching([
                $adminPersonaId => ['role' => 'admin']
            ]);
        }

        // Sincroniza tickets y deriva capacidad
        $this->syncTickets($evento, $request->input('tickets', []));
        $evento->capacidad = $this->computeCapacityFromTickets($evento);
        $evento->save();

        return redirect()->route('eventos.index')->with('success', 'Evento creado correctamente.');
    } catch (\Throwable $e) {
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

public function edit(Evento $evento)
{
    // El middleware de ruta (can:update,evento) ya chequea permisos.
    // Si llegás acá, todo bien. Superadmin ya entra siempre porque el before de la policy lo permite.
    return view('eventos.edit', compact('evento'));
}

public function update(Request $request, Evento $evento)
{
    // El middleware ya chequeó la policy y si pasa, puede actualizar.
    Log::debug('Eventos@update payload', $request->except('_token'));

    try {
        $validated = $request->validate([
            'nombre'        => ['required','string','max:255'],
            'descripcion'   => ['nullable','string'],
            'fecha_evento'  => ['required','date'],
            'hora_inicio'   => ['nullable','date_format:H:i'],
            'hora_cierre'   => ['nullable','date_format:H:i'],
            'ubicacion'     => ['nullable','string','max:255'],
            'localidad'     => ['nullable','string','max:255'],
            'provincia'     => ['nullable','string','max:255'],
            'estado'        => ['required','in:pendiente,aprobado,finalizado'],
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

        $before = $evento->provincia;
        $evento->provincia = $request->input('provincia');
        $evento->hora_inicio = $request->filled('hora_inicio') ? substr($request->input('hora_inicio'), 0, 5) : null;
        $evento->hora_cierre = $request->filled('hora_cierre') ? substr($request->input('hora_cierre'), 0, 5) : null;

        Log::info('UPDATE provincia', [
            'before' => $before,
            'input'  => $request->input('provincia'),
        ]);
        if (!$user->hasRole('superadmin')) {
            $evento->estado = $evento->getOriginal('estado');
        }
        $evento->save();

        Log::info('UPDATE provincia AFTER', [
            'after' => $evento->provincia,
            'dirty' => $evento->getChanges(),
        ]);

        // Tickets/capacidad
        $this->syncTickets($evento, $request->input('tickets', []));
        $evento->capacidad = $this->computeCapacityFromTickets($evento);
        $evento->save();

        return redirect()->route('eventos.index')->with('success', 'Evento actualizado');
    } catch (\Throwable $e) {
        Log::error('Error en Eventos@update', [
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
        return back()->withInput()->withErrors(['app' => $e->getMessage()]);
    }
}
public function destroy(Evento $evento)
{
    // Guardar las personas que estaban asociadas al evento ANTES de eliminarlo
    $personas = $evento->personas()->get();

    // Eliminar el evento (esto borra los pivotes evento_persona)
    $evento->delete();

    // Para cada persona, comprobar si sigue en algún evento
    foreach ($personas as $persona) {
        // Contar cuántos eventos tiene la persona después de eliminar este
        $eventosRestantes = $persona->eventos()->count();
        if ($eventosRestantes === 0) {
            $persona->delete(); // Elimina a la persona si ya no está en ningún evento
        }
    }

    return redirect()->route('eventos.index')->with('success', 'Evento y personas sin eventos, eliminados correctamente.');
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
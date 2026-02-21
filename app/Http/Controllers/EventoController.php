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

    public function index(Request $request)
{
    $user = auth()->user();

    if ($user->hasRole('superadmin')) {
        $query = \App\Models\Evento::with('personas')
            ->orderBy('fecha_evento', 'desc');

        // Agregamos el filtro por admin para superadmin
        if ($request->filled('admin_nombre')) {
            $query->whereHas('adminPersona', function($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->admin_nombre . '%');
            });
        }
        if ($request->filled('admin_email')) {
            $query->whereHas('adminPersona', function($q) use ($request) {
                $q->where('email', 'like', '%' . $request->admin_email . '%');
            });
        }
        if ($request->filled('admin_persona_id')) {
            $query->where('admin_persona_id', $request->admin_persona_id);
        }

        $eventos = $query->paginate(15);
    } else {
        $personaId = optional($user->persona)->id;

        $eventos = \App\Models\Evento::whereHas('personas', function ($q) use ($personaId) {
                $q->where('personas.id', $personaId)
                  ->whereIn('event_persona_roles.role', ['admin','subadmin']);
            })
            ->with('personas')
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
            'tickets'            => ['required','array','min:1','max:5'],
            'tickets.*.id'       => ['nullable','integer'],
            'tickets.*.nombre'   => ['nullable','string','max:100'],
            'tickets.*.precio'   => ['nullable','numeric','min:0'],
            'tickets.*.cupo'     => ['nullable','integer','min:0'],
            'tickets.*.activo'   => ['nullable','boolean'],
            'tickets.*._destroy' => ['nullable','boolean'],
        ];

        if ($isSuperadmin) {
            $rules = array_merge($rulesBase, [
                'admin_email'    => ['required','email'],
                'admin_nombre'   => ['nullable','string','max:255'],
                'admin_apellido' => ['nullable','string','max:255'], // NUEVO
                'admin_dni'      => ['nullable','string','max:32'],
            ]);
        } else {
            $rules = $rulesBase;
        }

        $data = $request->validate($rules);

        // === IDENTIFICAR Y OBTENER ADMIN/RESPONSABLE SEGÚN ROL ===
        if ($isSuperadmin) {
            // Buscar persona admin por email
            $adminPersona = \App\Models\Persona::where('email', $request->string('admin_email'))->first();

            if (!$adminPersona) {
                // Si no existe, validar todos los campos obligatorios
                $request->validate([
                    'admin_nombre'   => ['required','string','max:255'],
                    'admin_apellido' => ['required','string','max:255'],
                    'admin_dni'      => ['required','string','max:32'],
                ]);
                $adminPersona = \App\Models\Persona::create([
                    'email'    => (string) $request->string('admin_email'),
                    'nombre'   => (string) $request->string('admin_nombre'),
                    'apellido' => (string) $request->string('admin_apellido'),
                    'dni'      => (string) $request->string('admin_dni'),
                ]);
            } else {
                // Si falta apellido o dni, obligar a completarlos
                if (empty($adminPersona->apellido) || empty($adminPersona->dni)) {
                    $request->validate([
                        'admin_apellido' => ['required','string','max:255'],
                        'admin_dni'      => ['required','string','max:32'],
                    ]);
                    $adminPersona->apellido = (string) $request->string('admin_apellido');
                    $adminPersona->dni      = (string) $request->string('admin_dni');
                    $adminPersona->save();
                }
                // Si el nombre está vacío, opcional para completar también
                if (empty($adminPersona->nombre) && $request->filled('admin_nombre')) {
                    $adminPersona->nombre = (string) $request->string('admin_nombre');
                    $adminPersona->save();
                }
            }

            $adminUser = \App\Models\User::where('email', $adminPersona->email)->first();
            if (!$adminUser) {
                $adminUser = \App\Models\User::create([
                    'name'       => $adminPersona->nombre ?: $adminPersona->email,
                    'email'      => $adminPersona->email,
                    'password'   => bcrypt(\Illuminate\Support\Str::random(32)),
                    'persona_id' => $adminPersona->id,
                ]);
                \Illuminate\Support\Facades\Password::sendResetLink(['email' => $adminUser->email]);
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
                $adminPersona = \App\Models\Persona::create([
                    'email'  => $user->email,
                    'nombre' => $user->name,
                    'apellido' => '', // Podés agregar lógica para pedir apellido si querés
                    'dni'    => '',   // Podés agregar lógica para pedir dni si querés
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
        $evento = new \App\Models\Evento();
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
            'tickets'            => ['required','array','min:1','max:5'],
            'tickets.*.id'       => ['nullable','integer'],
            'tickets.*.nombre'   => ['nullable','string','max:100'],
            'tickets.*.precio'   => ['nullable','numeric','min:0'],
            'tickets.*.cupo'     => ['nullable','integer','min:0'],
            'tickets.*.activo'   => ['nullable','boolean'],
            'tickets.*._destroy' => ['nullable','boolean'],
        ]);
        // VALIDACIÓN REAL sobre los "activos"
            $tickets = collect($request->input('tickets', []))
            ->filter(fn($t) =>
                (empty($t['_destroy']) || $t['_destroy'] === "0")
                && isset($t['nombre']) && trim($t['nombre']) !== ''
            );
            if ($tickets->count() < 1) {
            return back()->withInput()->withErrors([
                'tickets' => 'Debe dejar al menos un tipo de entrada activo.'
            ]);
            }

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
        $user = auth()->user();
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
    $personas = $evento->personas()->get();

    \Log::debug('DESTROY: Eliminando evento', ['evento_id' => $evento->id]);
    $evento->delete();
    \Log::debug('DESTROY: Evento eliminado', ['evento_id' => $evento->id]);

    foreach ($personas as $persona) {
        $user = $persona->user;

        $quedaEnEventos = \DB::table('event_persona_roles')
            ->where('persona_id', $persona->id)
            ->whereIn('role', ['admin', 'subadmin', 'invitado'])
            ->exists();

        \Log::debug('DESTROY: Persona check', [
            'persona_id' => $persona->id,
            'quedaEnEventos' => $quedaEnEventos,
            'user_id' => $user ? $user->id : null,
        ]);

        if (!$quedaEnEventos) {
            $persona->delete();
            \Log::debug('DESTROY: Persona eliminada', ['persona_id' => $persona->id]);

            if ($user) {
                // Antes de borrar roles:
                \Log::debug('DESTROY: Roles antes de borrar', [
                    'roles' => $user->getRoleNames()
                ]);
                $user->syncRoles([]);
                $rows = \DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', get_class($user))
                    ->delete();
                \Log::debug('DESTROY: Model_has_roles elimina filas', ['user_id' => $user->id, 'rows_deleted' => $rows]);

                // Borrar user:
                $deleted = \DB::table('users')->where('id', $user->id)->delete();
                \Log::debug('DESTROY: Usuario eliminado', [
                    'user_id' => $user->id,
                    'deleted_rows' => $deleted,
                    'usuarios_restantes' => \DB::table('users')->where('id', $user->id)->count()
                ]);
            }
        } else {
            \Log::debug('DESTROY: Persona NO eliminada por eventos pendientes', ['persona_id' => $persona->id]);
        }
    }

    return redirect()->route('eventos.index')
        ->with('success', 'Evento y personas/usuarios eliminados si correspondía.');
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
public function cambiarAdmin(Request $request, Evento $evento)
{
   
    $request->validate([
        'admin_email'    => ['required', 'email'],
        'admin_nombre'   => ['required', 'string', 'max:255'],
        'admin_apellido' => ['required', 'string', 'max:255'],
        'admin_dni'      => ['required', 'string', 'max:32'],
    ]);

    // Paso 2: Chequear si la persona ya es admin de este evento
    $currentAdmin = $evento->personas()->wherePivot('role', 'admin')->first();

    if ($currentAdmin && strtolower($currentAdmin->email) === strtolower($request->admin_email)) {
        // Si el mismo admin, abortar y mostrar error
        return back()->withErrors([
            'admin_email' => 'La persona ya es administrador de este evento. Elegí otra para el cambio.'
        ])->withInput();
    }

    // Paso 3: Buscar o crear persona
    $persona = \App\Models\Persona::firstOrCreate(
        ['email' => $request->admin_email],
        [
            'nombre'   => $request->admin_nombre,
            'apellido' => $request->admin_apellido,
            'dni'      => $request->admin_dni,
        ]
    );

    // Paso 4: Buscar o crear user
    $user = \App\Models\User::firstOrCreate(
        ['email' => $request->admin_email],
        [
            'name'       => $persona->nombre . ' ' . $persona->apellido,
            'password'   => bcrypt(\Illuminate\Support\Str::random(32)),
            'persona_id' => $persona->id,
        ]
    );
    $user->assignRole('admin_evento'); // Asegura rol admin_evento

    // Paso 5: Sacar admin anterior de este evento (solo pivote)
    \DB::table('event_persona_roles')
        ->where('evento_id', $evento->id)
        ->where('role', 'admin')
        ->delete();

    // Paso 6: Asignar el nuevo admin en el pivote
    \DB::table('event_persona_roles')->updateOrInsert(
        [
            'evento_id'   => $evento->id,
            'persona_id'  => $persona->id,
            'role'        => 'admin'
        ],
        [] // otros campos del pivot si necesitas
    );

    // Paso 7: Mandar reset link si el user era nuevo
    if ($user->wasRecentlyCreated) {
        \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);
    }

    // Paso 8: (Opcional) Revisar que el admin anterior no quede huérfano (sin eventos ni roles) y decidir si lo archivás.

    return redirect()->route('eventos.show', $evento)->with('success', 'Administrador de evento cambiado correctamente.');
}
}
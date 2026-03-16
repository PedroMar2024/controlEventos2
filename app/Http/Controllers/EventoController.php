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
            $query = Evento::with('personas')
                ->orderBy('fecha_evento', 'desc');

            // Filtros de admin para superadmin
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

            $eventos = Evento::whereHas('personas', function ($q) use ($personaId) {
                    $q->where('personas.id', $personaId)
                      ->whereIn('event_persona_roles.role', ['admin_evento','subadmin_evento']);
                })
                ->with('personas')
                ->orderBy('fecha_evento','desc')
                ->paginate(15);
        }

        return view('eventos.index', compact('eventos'));
    }

    public function create()
    {
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
                'fecha_evento'  => [
                    'required',
                    'date',
                    'after_or_equal:' . now()->format('Y-m-d'),
                ],
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
                    'admin_apellido' => ['nullable','string','max:255'],
                    'admin_dni'      => ['nullable','string','max:32'],
                ]);
            } else {
                $rules = $rulesBase;
            }

            $data = $request->validate($rules);

            // === OBTENER ADMIN/RESPONSABLE SEGÚN ROL ===
            if ($isSuperadmin) {
                // Buscar persona admin por email
                $adminPersona = Persona::where('email', $request->string('admin_email'))->first();

                if (!$adminPersona) {
                    $request->validate([
                        'admin_nombre'   => ['required','string','max:255'],
                        'admin_apellido' => ['required','string','max:255'],
                        'admin_dni'      => ['required','string','max:32'],
                    ]);
                    $adminPersona = Persona::create([
                        'email'    => (string) $request->string('admin_email'),
                        'nombre'   => (string) $request->string('admin_nombre'),
                        'apellido' => (string) $request->string('admin_apellido'),
                        'dni'      => (string) $request->string('admin_dni'),
                    ]);
                } else {
                    if (empty($adminPersona->apellido) || empty($adminPersona->dni)) {
                        $request->validate([
                            'admin_apellido' => ['required','string','max:255'],
                            'admin_dni'      => ['required','string','max:32'],
                        ]);
                        $adminPersona->apellido = (string) $request->string('admin_apellido');
                        $adminPersona->dni      = (string) $request->string('admin_dni');
                        $adminPersona->save();
                    }
                    if (empty($adminPersona->nombre) && $request->filled('admin_nombre')) {
                        $adminPersona->nombre = (string) $request->string('admin_nombre');
                        $adminPersona->save();
                    }
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
                $adminUser = $user;
                $adminPersona = $user->persona;
                if (!$adminPersona) {
                    $adminPersona = Persona::create([
                        'email'  => $user->email,
                        'nombre' => $user->name,
                        'apellido' => '',
                        'dni'    => '',
                    ]);
                    $user->persona_id = $adminPersona->id;
                    $user->save();
                }
                $adminPersonaId = $adminPersona->id;
                // Si el usuario tiene el rol de subadmin_evento, lo asociamos como tal
                $adminRole = $user->hasRole('subadmin_evento') ? 'subadmin_evento' : 'admin_evento';
                $adminUser->assignRole($adminRole);
            }

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

            // Pivot rol admin/subadmin
            if (method_exists($evento, 'personas')) {
                $evento->personas()->syncWithoutDetaching([
                    $adminPersonaId => ['role' => $adminRole ?? 'admin_evento']
                ]);
            }

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
        return view('eventos.edit', compact('evento'));
    }

    public function update(Request $request, Evento $evento)
    {
        Log::debug('Eventos@update payload', $request->except('_token'));

        try {
            $validated = $request->validate([
                'nombre'        => ['required','string','max:255'],
                'descripcion'   => ['nullable','string'],
                'fecha_evento'  => [
                    'required',
                    'date',
                    'after_or_equal:' . now()->format('Y-m-d'),
                ],
                'hora_inicio'   => ['nullable','date_format:H:i'],
                'hora_cierre'   => ['nullable','date_format:H:i'],
                'ubicacion'     => ['nullable','string','max:255'],
                'localidad'     => ['nullable','string','max:255'],
                'provincia'     => ['nullable','string','max:255'],
                'estado'        => ['required','in:pendiente,aprobado,finalizado'],
                'publico'       => ['boolean'],
                'reingreso'     => ['nullable','boolean'],
                'tickets'            => ['required','array','min:1','max:5'],
                'tickets.*.id'       => ['nullable','integer'],
                'tickets.*.nombre'   => ['nullable','string','max:100'],
                'tickets.*.precio'   => ['nullable','numeric','min:0'],
                'tickets.*.cupo'     => ['nullable','integer','min:0'],
                'tickets.*.activo'   => ['nullable','boolean'],
                'tickets.*._destroy' => ['nullable','boolean'],
                'admin_email'    => ['required','email'],
                'admin_nombre'   => ['required','string','max:255'],
                'admin_apellido' => ['required','string','max:255'],
                'admin_dni'      => ['required','string','max:255'],
            ]);

            $tickets = collect($request->input('tickets', []))
                ->filter(fn($t) => (empty($t['_destroy']) || $t['_destroy'] === "0") && isset($t['nombre']) && trim($t['nombre']) !== '');
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

            // LÓGICA ADMIN
            $adminPersona = $evento->adminPersona;
            $adminEmailActual = $adminPersona ? $adminPersona->email : null;
            $adminEmailNuevo  = $validated['admin_email'];
            $huboCambioAdmin = $adminEmailActual !== $adminEmailNuevo;

            if ($huboCambioAdmin && !$user->hasRole('superadmin')) {
                return response()->view('eventos.no_superadmin', [
                    'evento' => $evento,
                    'mensaje' => 'Solo el superadmin puede cambiar el administrador del evento.',
                ]);
            }

            // Si email no cambia, actualiza datos de admin actual
            if ($adminPersona && !$huboCambioAdmin) {
                $adminPersona->nombre   = $validated['admin_nombre'];
                $adminPersona->apellido = $validated['admin_apellido'];
                $adminPersona->dni      = $validated['admin_dni'];
                $adminPersona->save();
            }
            // Si email cambia, cambiar admin del evento
            else if ($huboCambioAdmin) {
                $personaNueva = Persona::where('email', $validated['admin_email'])->first();
                if (!$personaNueva) {
                    $personaNueva = Persona::create([
                        'nombre'   => $validated['admin_nombre'],
                        'apellido' => $validated['admin_apellido'],
                        'dni'      => $validated['admin_dni'],
                        'email'    => $validated['admin_email'],
                    ]);
                    $userNuevo = User::create([
                        'name'       => $validated['admin_nombre'] . ' ' . $validated['admin_apellido'],
                        'email'      => $validated['admin_email'],
                        'persona_id' => $personaNueva->id,
                        'password'   => bcrypt(Str::random(10)),
                    ]);
                    $userNuevo->assignRole('admin_evento');
                    Password::sendResetLink(['email' => $userNuevo->email]);
                }
                $evento->admin_persona_id = $personaNueva->id;
                $evento->save();

                // Cambiar relación en tabla pivot (event_persona_roles)
                $evento->personas()->detach($adminPersona->id); // Saca admin anterior
                $evento->personas()->attach($personaNueva->id, ['role' => 'admin_evento']);

                // Chequear si admin anterior queda huérfano
                $adminViejo = $adminPersona;
                $quedaEnEventos = \DB::table('event_persona_roles')
                    ->where('persona_id', $adminViejo->id)
                    ->whereIn('role', ['admin_evento', 'subadmin_evento', 'invitado'])
                    ->exists();

                $userViejo = $adminViejo->user;

                if (!$quedaEnEventos) {
                    $adminViejo->delete();
                    if ($userViejo) {
                        $userViejo->syncRoles([]);
                        \DB::table('model_has_roles')
                            ->where('model_id', $userViejo->id)
                            ->where('model_type', get_class($userViejo))
                            ->delete();
                        \DB::table('users')->where('id', $userViejo->id)->delete();
                    }
                }
            }

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

        Log::debug('DESTROY: Eliminando evento', ['evento_id' => $evento->id]);
        $eventoId = $evento->id;
        $evento->delete();
        Log::debug('DESTROY: Evento eliminado', ['evento_id' => $eventoId]);

        $eliminados = \App\Services\PersonaCleanupService::cleanup($personas, $eventoId);

        $mensaje = 'Evento y personas/usuarios eliminados si correspondía.';
        if (!empty($eliminados)) {
            $mensaje .= ' Es probable que las siguientes personas hayan sido eliminadas del sistema por ser su último evento: ' . implode(', ', $eliminados) . '.';
        }

        return redirect()->route('eventos.index')->with('success', $mensaje);
    }

    public function aprobar(Evento $evento)
    {
        $this->authorize('approve', $evento);
        $evento->estado = 'aprobado';
        $evento->save();
        return redirect()->route('eventos.index')->with('success', 'Evento aprobado');
    }

    public function cancelar(Evento $evento)
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
            ->sum(fn($t) => (int)($t->cupo ?? 0));
    }

    public function cambiarAdmin(Request $request, Evento $evento)
    {
        $request->validate([
            'admin_email'    => ['required', 'email'],
            'admin_nombre'   => ['required', 'string', 'max:255'],
            'admin_apellido' => ['required', 'string', 'max:255'],
            'admin_dni'      => ['required', 'string', 'max:32'],
        ]);

        // Chequear admin actual usando admin_evento
        $currentAdmin = $evento->personas()->wherePivot('role', 'admin_evento')->first();

        if ($currentAdmin && strtolower($currentAdmin->email) === strtolower($request->admin_email)) {
            return back()->withErrors([
                'admin_email' => 'La persona ya es administrador de este evento. Elegí otra para el cambio.'
            ])->withInput();
        }

        $persona = Persona::firstOrCreate(
            ['email' => $request->admin_email],
            [
                'nombre'   => $request->admin_nombre,
                'apellido' => $request->admin_apellido,
                'dni'      => $request->admin_dni,
            ]
        );

        $user = User::firstOrCreate(
            ['email' => $request->admin_email],
            [
                'name'       => $persona->nombre . ' ' . $persona->apellido,
                'password'   => bcrypt(Str::random(32)),
                'persona_id' => $persona->id,
            ]
        );
        $user->assignRole('admin_evento');

        // Sacar admin anterior del pivote usando admin_evento
        \DB::table('event_persona_roles')
            ->where('evento_id', $evento->id)
            ->where('role', 'admin_evento')
            ->delete();

        // Asignar nuevo admin
        \DB::table('event_persona_roles')->updateOrInsert(
            [
                'evento_id'   => $evento->id,
                'persona_id'  => $persona->id,
                'role'        => 'admin_evento'
            ],
            []
        );

        if ($user->wasRecentlyCreated) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return redirect()->route('eventos.show', $evento)->with('success', 'Administrador de evento cambiado correctamente.');
    }

    public function agregarInvitadoPendiente(Request $request, Evento $evento)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $existe = \App\Models\InvitacionEvento::where('evento_id', $evento->id)
                    ->where('email', $request->email)
                    ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Ese correo ya está en la bandeja de invitaciones para este evento.'
            ]);
        }

        \App\Models\InvitacionEvento::create([
            'evento_id' => $evento->id,
            'email' => $request->email,
        ]);

        return response()->json([
            'success' => true,
            'mensaje' => 'Invitado agregado a la bandeja. Cuando decidas, podrás enviarle la notificación.'
        ]);
    }
}
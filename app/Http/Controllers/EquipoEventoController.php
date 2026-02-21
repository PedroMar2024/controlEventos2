<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class EquipoEventoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    // Lista admin principal y subadmins del evento
    public function index(Evento $evento)
    {
        $admin = $evento->adminPersona; // admin_persona_id
        $subadmins = $evento->personas()->wherePivot('role', 'subadmin')->get();
        $count = $subadmins->count();

        return view('eventos.equipo', compact('evento', 'admin', 'subadmins', 'count'));
    }

    // Agregar subadmin por email (envía reset SOLO si el usuario es nuevo)
    public function store(Request $request, Evento $evento)
{
    $data = $request->validate([
        'nombre'   => ['required', 'string', 'max:255'],
        'apellido' => ['required', 'string', 'max:255'],
        'dni'      => ['required', 'string', 'max:255', 'unique:personas,dni'],
        'email'    => [
            'required',
            'email',
            'max:255',
            'unique:users,email',
            'unique:personas,email'
        ],
    ]);

    // Límite 10 subadmins
    $current = $evento->personas()->wherePivot('role', 'subadmin')->count();
    if ($current >= 10) {
        return back()->with('error', 'Máximo 10 subadmins por evento.')->withInput();
    }

    // Crear Persona (controla duplicidad por unique en email/dni)
    $persona = \App\Models\Persona::create([
        'nombre'   => $data['nombre'],
        'apellido' => $data['apellido'],
        'dni'      => $data['dni'],
        'email'    => $data['email'],
    ]);

    // Evitar duplicado como subadmin en el evento (por si acaso)
    $exists = $evento->personas()
        ->where('personas.id', $persona->id)
        ->wherePivot('role', 'subadmin')
        ->exists();
    if ($exists) {
        return back()->with('error', 'Esta persona ya es subadmin del evento.')->withInput();
    }

    // Bloqueos:
    // 1) el actor no puede agregarse a sí mismo
    $actorPersonaId = optional(auth()->user()->persona)->id;
    if ($actorPersonaId && $persona->id === $actorPersonaId) {
        return back()->with('error', 'No podés agregarte a vos mismo como subadmin de este evento.')->withInput();
    }

    // 2) el admin del evento no puede ser subadmin
    if ((int)$evento->admin_persona_id === (int)$persona->id) {
        return back()->with('error', 'El admin del evento no puede ser agregado como subadmin.')->withInput();
    }

    // 3) si ya es admin en el pivot, no permitir subadmin
    $esAdminPivot = $evento->personas()
        ->where('personas.id', $persona->id)
        ->wherePivot('role', 'admin')
        ->exists();
    if ($esAdminPivot) {
        return back()->with('error', 'Esta persona ya es admin del evento.')->withInput();
    }

    DB::transaction(function () use ($evento, $persona, $data) {
        // Adjuntar como subadmin en pivot
        $evento->personas()->attach($persona->id, ['role' => 'subadmin']);

        // Crear User y vincularlo
        $user = \App\Models\User::create([
            'name'       => $data['nombre'] . ' ' . $data['apellido'],
            'email'      => $data['email'],
            'password'   => bcrypt(Str::random(32)),
            'persona_id' => $persona->id,
        ]);
        // Asignar rol global
        $user->assignRole('subadmin_evento');

        \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);
        \Illuminate\Support\Facades\Log::info('EquipoEvento: Subadmin creado y reset enviado', ['email' => $user->email]);
    });

    return back()->with('status', 'Subadmin agregado.');
}

public function destroy(Evento $evento, Persona $persona)
{
    // 1. Eliminar el vínculo en el pivot SOLO para este evento/rol
    DB::table('event_persona_roles')
        ->where('evento_id', $evento->id)
        ->where('persona_id', $persona->id)
        ->where('role', 'subadmin')
        ->delete();

    // 2. ¿La persona quedó en algún evento?
    $sigueEnEvento = DB::table('event_persona_roles')
        ->where('persona_id', $persona->id)
        ->exists();

    // 3. Borrar usuario/roles/persona SOLO si ya NO está en ningún evento
    $user = \App\Models\User::where('persona_id', $persona->id)->first();

    if (!$sigueEnEvento) {
        // a. Borrar todos los roles globales del user
        if ($user) {
            $user->roles()->detach(); // borra model_has_roles
            // b. BORRAR user usando forceDelete siempre
            $user->forceDelete();
            // c. DOBLE chequeo en model_has_roles
            DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', 'App\\Models\\User')->delete();
        }

        // d. BORRAR persona usando forceDelete
        $persona->forceDelete();

    } elseif ($user) {
        // Solo quitarle el rol global si ya no es subadmin en nada
        $esSubAdminEnAlgo = DB::table('event_persona_roles')
                ->where('persona_id', $persona->id)
                ->where('role', 'subadmin')
                ->exists();
        if (!$esSubAdminEnAlgo && $user->hasRole('subadmin_evento')) {
            $user->removeRole('subadmin_evento');
        }
    }

    return back()->with('status', 'Subadmin y fichas limpias si no tenía ningún evento.');
}
}
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
            'email'  => ['required','email'],
            'nombre' => ['nullable','string','max:255'],
        ]);

        // Límite 10 subadmins
        $current = $evento->personas()->wherePivot('role', 'subadmin')->count();
        if ($current >= 10) {
            return back()->with('error', 'Máximo 10 subadmins por evento.')->withInput();
        }

        // Buscar o crear Persona
        $persona = Persona::firstOrCreate(
            ['email' => $data['email']],
            ['nombre' => $data['nombre'] ?? null]
        );

        // Evitar duplicado como subadmin
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

            // Asegurar User vinculado a Persona
            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                // Usuario nuevo: se crea y se envía reset
                $user = User::create([
                    'name'       => $persona->nombre ?? $data['email'],
                    'email'      => $data['email'],
                    'password'   => bcrypt(Str::random(12)), // temporal
                    'persona_id' => $persona->id,
                ]);

                $status = Password::sendResetLink(['email' => $user->email]);
                Log::info('EquipoEvento: reset link enviado (usuario nuevo)', ['email' => $user->email, 'status' => $status]);
            } else {
                // Usuario existente: NO enviar reset. Solo asegurar vínculo de Persona si faltara.
                if (!$user->persona_id) {
                    $user->persona_id = $persona->id;
                    $user->save();
                }
                Log::info('EquipoEvento: usuario existente, sin reset', ['email' => $user->email]);
            }
        });

        return back()->with('status', 'Subadmin agregado.');
    }

    // Quitar subadmin del evento (elimina solo el rol subadmin en el pivot)
    public function destroy(Evento $evento, Persona $persona)
    {
        DB::table('event_persona_roles')
            ->where('evento_id', $evento->id)
            ->where('persona_id', $persona->id)
            ->where('role', 'subadmin')
            ->delete();

        return back()->with('status', 'Subadmin quitado del evento.');
    }
}
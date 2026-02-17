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
        $admin = $evento->adminPersona;
        $subadmins = $evento->personas()->wherePivot('role', 'subadmin')->get();
        $count = $subadmins->count();

        return view('eventos.equipo', compact('evento', 'admin', 'subadmins', 'count'));
    }

    // Agregar subadmin
    public function store(Request $request, Evento $evento)
    {
        $data = $request->validate([
            'email'    => ['required','email','max:255'],
            'nombre'   => ['nullable','string','max:255'],
            'apellido' => ['nullable','string','max:255'],
            'dni'      => ['nullable','string','max:255'],
        ]);

        // Límite de subadmins
        $current = $evento->personas()->wherePivot('role', 'subadmin')->count();
        if ($current >= 10) {
            return back()->with('error', 'Máximo 10 subadmins por evento.')->withInput();
        }

        // ¿Existe la persona?
        $persona = Persona::where('email', $data['email'])->first();

        if ($persona) {
            // Si existe, chequea reglas y rol duplicado
            $exists = $evento->personas()
                ->where('personas.id', $persona->id)
                ->wherePivot('role', 'subadmin')
                ->exists();
            if ($exists) {
                return back()->with('error', 'Esta persona ya es subadmin del evento.')->withInput();
            }

            // Chequea si quien intenta cargarse a sí mismo
            $actorPersonaId = optional(auth()->user()->persona)->id;
            if ($actorPersonaId && $persona->id === $actorPersonaId) {
                return back()->with('error', 'No podés agregarte como subadmin de este evento.')->withInput();
            }
            // Chequea si es admin principal
            if ((int)$evento->admin_persona_id === (int)$persona->id) {
                return back()->with('error', 'El admin del evento no puede ser subadmin.')->withInput();
            }
            // Chequea roles en ese evento
            $esAdminPivot = $evento->personas()
                ->where('personas.id', $persona->id)
                ->wherePivot('role', 'admin')
                ->exists();
            if ($esAdminPivot) {
                return back()->with('error', 'Esta persona ya es admin del evento.')->withInput();
            }

        } else {
            // Validación cuando es NUEVA persona: requiere los datos completos
            $request->validate([
                'nombre'   => ['required','string','max:255'],
                'apellido' => ['required','string','max:255'],
                'dni'      => ['required','string','max:255', 'unique:personas,dni'],
                'email'    => ['required','email','max:255', 'unique:personas,email', 'unique:users,email'],
            ]);
            // Crear nueva persona con todos los datos
            $persona = Persona::create([
                'nombre'   => $data['nombre'],
                'apellido' => $data['apellido'],
                'dni'      => $data['dni'],
                'email'    => $data['email'],
            ]);
        }

        // Asociar en la tabla pivote como subadmin
        DB::transaction(function () use ($evento, $persona, $data) {
            $evento->personas()->attach($persona->id, ['role' => 'subadmin']);

            // Crear user si no existe
            $user = User::where('email', $persona->email)->first();
            if (!$user) {
                $user = User::create([
                    'name'       => $persona->nombre . ' ' . $persona->apellido,
                    'email'      => $persona->email,
                    'password'   => bcrypt(Str::random(12)),
                    'persona_id' => $persona->id,
                ]);
                Password::sendResetLink(['email' => $user->email]);
                Log::info('EquipoEvento: reset enviado a NUEVO', ['email' => $user->email]);
            } else {
                if (!$user->persona_id) {
                    $user->persona_id = $persona->id;
                    $user->save();
                }
                Log::info('EquipoEvento: usuario existente, sin reset', ['email' => $user->email]);
            }
        });

        return back()->with('status', 'Subadmin agregado correctamente.');
    }

    // Quitar subadmin
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
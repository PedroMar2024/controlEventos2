<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

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

    // Agregar subadmin por email (crea Persona si no existe; crea User y envía reset password si no existe)
    public function store(Request $request, Evento $evento)
    {
        $data = $request->validate([
            'email'  => ['required','email'],
            'nombre' => ['nullable','string','max:255'],
        ]);

        // Límite 10 subadmins
        $current = $evento->personas()->wherePivot('role', 'subadmin')->count();
        if ($current >= 10) {
            return back()->withErrors(['limit' => 'Máximo 10 subadmins por evento.'])->withInput();
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
            return back()->withErrors(['duplicate' => 'Esta persona ya es subadmin del evento.'])->withInput();
        }

        DB::transaction(function () use ($evento, $persona, $data) {
            // Adjuntar como subadmin en pivot
            $evento->personas()->attach($persona->id, ['role' => 'subadmin']);

            // Asegurar User vinculado a Persona
            $user = User::where('email', $data['email'])->first();
            if (!$user) {
                $user = User::create([
                    'name'       => $persona->nombre ?? $data['email'],
                    'email'      => $data['email'],
                    'password'   => bcrypt(str()->random(12)), // temporal
                    'persona_id' => $persona->id,
                ]);
            }

            // Enviar link de reset de contraseña para que defina su password
            $status = Password::sendResetLink(['email' => $data['email']]);
            Log::info('EquipoEvento: sendResetLink', ['email' => $data['email'], 'status' => $status]);
        });

        return back()->with('status', 'Subadmin agregado. Se envió email para definir su contraseña.');
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
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Evento;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AdminManagementController extends Controller
{
    public function index()
    {
        $admins = User::role('admin_evento')->with('persona')->orderBy('name')->paginate(20);
        return view('admins.index', compact('admins'));
    }

    public function events(User $user)
    {
        $eventos = Evento::whereHas('personas', function ($q) use ($user) {
                $pid = optional($user->persona)->id;
                $q->where('personas.id', $pid)->where('event_persona_roles.role', 'admin');
            })
            ->orderBy('fecha_evento','desc')
            ->paginate(20);

        return view('admins.events', compact('user', 'eventos'));
    }

    public function create()
    {
        return view('admins.create');
    }

    public function store(Request $request)
{
    $data = $request->validate([
        'nombre'   => ['required', 'string', 'max:255'],
        'apellido' => ['required', 'string', 'max:255'],
        'dni'      => ['required', 'string', 'max:255', 'unique:personas,dni'],
        'email'    => ['required', 'email', 'max:255', 'unique:users,email', 'unique:personas,email'],
    ]);

    // Crear Persona primero, sí o sí con todos los datos completos (ya controla duplicidad por unique en dni/email)
    $persona = \App\Models\Persona::create([
        'nombre'   => $data['nombre'],
        'apellido' => $data['apellido'],
        'dni'      => $data['dni'],
        'email'    => $data['email'],
    ]);

    // Crear User y vincularlo
    $user = \App\Models\User::create([
        'name'       => $data['nombre'] . ' ' . $data['apellido'],
        'email'      => $data['email'],
        'password'   => bcrypt(Str::random(32)),
        'persona_id' => $persona->id,
    ]);

    $user->assignRole('admin_evento');

    \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);

    return redirect()->route('admins.index')->with('status', 'Administrador creado. Se envió email de reseteo.');
}
    
    public function edit(User $user)
    {
        return view('admins.edit', ['user' => $user]);
    }
 
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'nombre'   => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'dni'      => ['required', 'string', 'max:255', 'unique:personas,dni,'.$user->persona_id],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id, 'unique:personas,email,'.$user->persona_id],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
    
        // Actualiza la persona vinculada
        $persona = $user->persona;
        $persona->nombre   = $data['nombre'];
        $persona->apellido = $data['apellido'];
        $persona->dni      = $data['dni'];
        $persona->email    = $data['email'];
        $persona->save();
    
        // Actualiza el usuario
        $user->name  = $data['nombre'] . ' ' . $data['apellido'];
        $emailCambio = $user->email !== $data['email'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = bcrypt($data['password']);
        }
        
        // Si el email fue cambiado, desvalida y manda mail de confirmación
        if ($emailCambio) {
            $user->email_verified_at = null;
            $user->save();
    
            if (method_exists($user, 'sendEmailVerificationNotification')) {
                $user->sendEmailVerificationNotification();
            }
        } else {
            $user->save();
        }
    
        return redirect()->route('admins.index')->with('status', 'Administrador actualizado correctamente.');
    }
    
    public function destroy(User $user)
{
    $this->authorize('delete', $user);
    // Chequeo 1: ¿Tiene OTROS roles además de admin_evento?
    $otrosRoles = $user->roles()->where('name', '!=', 'admin_evento')->count();
    if ($otrosRoles > 0) {
        return back()->with('error', 'No se puede eliminar: el usuario tiene otros roles asignados.');
    }

    // Chequeo 2: ¿Es admin de algún evento?
    $tieneEventos = $user->persona
        ? $user->persona->eventosComoAdmin()->count()
        : 0;
    if ($tieneEventos > 0) {
        return back()->with('error', 'No se puede eliminar: el usuario es administrador de uno o más eventos.');
    }

    // Proteger superadmin (no borrar accidentalmente)
    if ($user->hasRole('superadmin')) {
        return back()->with('error', 'No se puede eliminar un superadmin.');
    }

    // Si pasa todo, eliminar
    $user->delete();

    return redirect()->route('admins.index')->with('status', 'Administrador eliminado correctamente.');
}
}
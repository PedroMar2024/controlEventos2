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
    // SIEMPRE tenés el user,
    // 1. Buscá la persona asociada
    $persona = $user->persona;

    // 2. Buscá el evento donde está como admin (ajustá el WHERE si tienes muchos)
    // Suponiendo que una persona puede ser admin de más de un evento, elegí 1 o pedí la lista
    $eventoAdmin = \DB::table('event_persona_roles')
        ->where('persona_id', $persona->id)
        ->where('role', 'admin')
        ->join('eventos', 'eventos.id', '=', 'event_persona_roles.evento_id')
        ->select('eventos.*')
        ->first();

    return view('admins.edit', [
        'user' => $user,
        'evento' => $eventoAdmin, // PASARLO A LA VISTA
    ]);
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
    
        // No permitir eliminar superadmin ni admins con roles extra
        $otrosRoles = $user->roles()->where('name', '!=', 'admin_evento')->count();
        if ($otrosRoles > 0) {
            return back()->with('error', 'No se puede eliminar: el usuario tiene otros roles asignados.');
        }
        if ($user->hasRole('superadmin')) {
            return back()->with('error', 'No se puede eliminar un superadmin.');
        }
    
        // Traer persona asociada
        $persona = $user->persona;
    
        // --- BLOQUE CRÍTICO ---
        // No permitir eliminar si persona está asociada a algún evento (admin, subadmin o invitado)
        if ($persona) {
            $vinculadoAEvento = \DB::table('event_persona_roles')
                ->where('persona_id', $persona->id)
                ->whereIn('role', ['admin', 'subadmin', 'invitado'])
                ->exists();
            if ($vinculadoAEvento) {
                return back()->with('error', 'No se puede eliminar: la persona está asociada a al menos un evento (admin, subadmin o invitado).');
            }
        }
    
        // Antes de limpiar, eliminar cualquier vínculo con eventos en la tabla pivote (por si quedó algo colgado)
        if ($persona) {
            \DB::table('event_persona_roles')->where('persona_id', $persona->id)->delete();
        }
    
        // Limpiar roles globales en model_has_roles
        $user->roles()->detach();
    
        // Borrar el usuario
        $user->delete();
    
        // Si la persona queda huérfana, eliminarla
        if ($persona) {
            $enEventos = \DB::table('event_persona_roles')->where('persona_id', $persona->id)->exists();
            $tieneVinculoUser = \App\Models\User::where('persona_id', $persona->id)->exists();
            if (!$enEventos && !$tieneVinculoUser) {
                $persona->delete();
            }
        }
    
        return redirect()->route('admins.index')->with('status', 'Administrador y ficha eliminados correctamente.');
    }
    public function eventos(User $admin)
{
    // Sacar la persona asociada
    $persona = $admin->persona;
    if (!$persona) {
        // Si no hay persona, puede que sea un bug...
        return redirect()->route('admins.index')->with('error', 'Este admin no tiene persona asociada.');
    }

    // Buscar todos los eventos donde es admin/subadmin/invitado (usando el pivot)
    $eventos = $persona->eventos()->withPivot('role')->with('tickets')->get();

    return view('admins.eventos', compact('admin', 'eventos'));
}
}
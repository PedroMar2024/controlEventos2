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
            'name'  => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt(Str::random(32)),
        ]);

        $persona = Persona::firstOrCreate(['email' => $user->email], ['nombre' => $user->name]);
        $user->persona_id = $persona->id;
        $user->save();

        $user->assignRole('admin_evento');

        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('admins.index')->with('status', 'Administrador creado. Se envió email de reseteo.');
    }
    
    public function edit(User $user)
{
    return view('admins.edit', compact('user'));
}
 
public function update(Request $request, User $user)
{
    $data = $request->validate([
        'name'  => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        'password' => ['nullable', 'string', 'min:8'],
    ]);

    $user->name = $data['name'];
    $user->email = $data['email'];
    if (!empty($data['password'])) {
        $user->password = bcrypt($data['password']);
    }
    $user->save();

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
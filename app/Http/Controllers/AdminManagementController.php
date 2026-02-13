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
}
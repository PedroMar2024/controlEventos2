<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Evento;

class AdminManagementController extends Controller
{
    public function index()
    {
        $admins = User::role('admin_evento')->with('persona')->orderBy('name')->paginate(20);
        return view('admins.index', compact('admins'));
    }

    public function events(User $user)
    {
        // Eventos donde $user es admin del evento (pivot)
        $eventos = Evento::whereHas('personas', function ($q) use ($user) {
                $pid = optional($user->persona)->id;
                $q->where('personas.id', $pid)->where('event_persona_roles.role', 'admin');
            })
            ->orderBy('fecha_evento','desc')
            ->paginate(20);

        return view('admins.events', compact('user', 'eventos'));
    }
}
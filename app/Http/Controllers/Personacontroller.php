<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Illuminate\Http\Request;

class PersonaController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function findByEmail(Request $request)
{
    $request->validate(['email' => ['required','email']]);
    $p = Persona::where('email', $request->string('email'))->first();

    if (!$p) {
        return response()->json(['found' => false]);
    }

    return response()->json([
        'found' => true,
        'id' => $p->id,
        'nombre' => $p->nombre,
        'apellido' => $p->apellido,
        'dni' => $p->dni,
        'email' => $p->email,
    ]);
}
}
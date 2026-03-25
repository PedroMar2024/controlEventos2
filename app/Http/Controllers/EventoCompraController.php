<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\EventoRolService;

class EventoCompraController extends Controller
{
    // Botón 1: Muestra el formulario de compra de entradas, SOLO si el evento es público
    public function showForm(Evento $evento)
    {
        if (!$evento->publico) {
            abort(404); // Solo acceder si es evento público
        }
        return view('eventos.publico_comprar', compact('evento'));
    }

    // Botón 2: Procesa el pedido de compra de entradas
    public function procesarCompra(Request $request, Evento $evento)
    {
        if (!$evento->publico) {
            abort(404);
        }

        $data = $request->validate([
            'dni'      => ['required', 'string', 'max:32'],
            'nombre'   => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        DB::beginTransaction();
        try {
            // Usa el service central, como corresponde en tu taller
            $service = new EventoRolService();
            $service->asignarPersonaARolEnEvento($evento->id, [
                'dni'      => $data['dni'],
                'nombre'   => $data['nombre'],
                'apellido' => $data['apellido'],
                'email'    => $data['email'],
            ], 'invitado');

            // Aquí deberías guardar la solicitud de compra y la cantidad pedida -
            // (en una tabla de "entradas reservadas" o "pedidos de tickets",
            // lo cual es el siguiente cable si querés avanzar el flujo completo).

            DB::commit();

            return redirect()->route('eventos.publico.comprar', $evento->id)
                ->with('success', 'Tu solicitud fue recibida. Pronto recibirás tus entradas por email.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors([
                'app' => 'Error al procesar la compra: ' . $e->getMessage()
            ]);
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

class EventoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('superadmin')) {
            $eventos = Evento::orderBy('fecha_evento','desc')->paginate(15);
        } else {
            $personaId = optional($user->persona)->id;
            $eventos = Evento::where('publico', true)
                ->orWhereHas('personas', function ($q) use ($personaId) {
                    $q->where('personas.id', $personaId);
                })
                ->orderBy('fecha_evento','desc')
                ->paginate(15);
        }

        return view('eventos.index', compact('eventos'));
    }

    public function create()
    {
        // Superadmin pasa siempre
        return view('eventos.create');
    }

    public function store(Request $request)
    {
        Log::debug('Eventos@store payload', $request->except('_token'));

        try {
            $data = $request->validate([
                'nombre'        => ['required','string','max:255'],
                'descripcion'   => ['nullable','string'],
                'fecha_evento'  => ['nullable','date'],
                'hora_inicio'   => ['nullable','date_format:H:i'],
                'hora_cierre'   => ['nullable','date_format:H:i'],
                'ubicacion'     => ['nullable','string','max:255'],
                'localidad'     => ['nullable','string','max:255'],
                'provincia'     => ['nullable','string','max:255'],
                'capacidad'     => ['nullable','integer','min:1'],
                'estado'        => ['nullable','in:pendiente,aprobado,finalizado'],
                'precio_evento' => ['nullable','numeric','min:0'],
                'publico'       => ['nullable','boolean'],
                'reingreso'     => ['nullable','boolean'],

                // Admin por email
                'admin_email'   => ['required','email'],
                'admin_nombre'  => ['nullable','string','max:255'],
                'admin_dni'     => ['nullable','string','max:32'],
            ]);

            // Persona admin por email
            $adminPersona = Persona::where('email', $request->string('admin_email'))->first();
            if (!$adminPersona) {
                $request->validate([
                    'admin_nombre'  => ['required','string','max:255'],
                    'admin_dni'     => ['required','string','max:32'],
                ]);

                $adminPersona = Persona::create([
                    'email'  => (string) $request->string('admin_email'),
                    'nombre' => (string) $request->string('admin_nombre'),
                    'dni'    => (string) $request->string('admin_dni'),
                ]);
            }

            // Normalizar checkboxes
            $data['publico']   = $request->boolean('publico');
            $data['reingreso'] = $request->boolean('reingreso');

            // Crear evento
            $evento = new Evento();
            foreach ($evento->getFillable() as $col) {
                if (array_key_exists($col, $data)) {
                    $evento->{$col} = $data[$col];
                }
            }
            // Forzar provincia con lo que llega (evita que quede un valor previo o vacío)
            $evento->provincia = $request->input('provincia');

            $evento->admin_persona_id = $adminPersona->id;

            Log::info('STORE provincia', [
                'input' => $request->input('provincia'),
            ]);

            $evento->save();

            Log::info('STORE provincia AFTER', [
                'persisted' => $evento->provincia,
            ]);

            // Pivot rol admin
            if (method_exists($evento, 'personas')) {
                $evento->personas()->syncWithoutDetaching([
                    $adminPersona->id => ['role' => 'admin']
                ]);
            }

            return redirect()->route('eventos.index')->with('success', 'Evento creado correctamente.');
        } catch (Throwable $e) {
            Log::error('Error en Eventos@store', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
            return back()->withInput()->withErrors(['app' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $evento = Evento::findOrFail($id);

        // Si no es público y NO sos superadmin, chequea permiso
        if (!$evento->publico && !auth()->user()->hasRole('superadmin')) {
            Gate::authorize('ver-evento', $evento);
        }

        return view('eventos.show', compact('evento'));
    }

    public function edit($id)
    {
        $evento = Evento::findOrFail($id);

        // Bypass para superadmin
        if (!auth()->user()->hasRole('superadmin')) {
            Gate::authorize('editar-evento', $evento);
        }

        return view('eventos.edit', compact('evento'));
    }

    public function update(Request $request, $id)
    {
        Log::debug('Eventos@update payload', $request->except('_token'));

        try {
            $evento = Evento::findOrFail($id);

            if (!auth()->user()->hasRole('superadmin')) {
                Gate::authorize('editar-evento', $evento);
            }

            $validated = $request->validate([
                'nombre'        => ['required','string','max:255'],
                'descripcion'   => ['nullable','string'],
                'fecha_evento'  => ['required','date'],
                'hora_inicio'   => ['nullable','date_format:H:i'],
                'hora_cierre'   => ['nullable','date_format:H:i'],
                'ubicacion'     => ['nullable','string','max:255'],
                'localidad'     => ['nullable','string','max:255'],
                'provincia'     => ['nullable','string','max:255'],
                'capacidad'     => ['nullable','integer','min:1'],
                'estado'        => ['required','in:pendiente,aprobado,finalizado'],
                'precio_evento' => ['nullable','numeric','min:0'],
                'publico'       => ['boolean'],
                'reingreso'     => ['nullable','boolean'],
            ]);

            $validated['publico']   = $request->boolean('publico');
            $validated['reingreso'] = $request->boolean('reingreso');

            foreach ($evento->getFillable() as $col) {
                if (array_key_exists($col, $validated)) {
                    $evento->{$col} = $validated[$col];
                }
            }

            // Forzar provincia con lo que llega
            $before = $evento->provincia;
            $evento->provincia = $request->input('provincia');

            Log::info('UPDATE provincia', [
                'before' => $before,
                'input'  => $request->input('provincia'),
            ]);

            $evento->save();

            Log::info('UPDATE provincia AFTER', [
                'after' => $evento->provincia,
                'dirty' => $evento->getChanges(),
            ]);

            return redirect()->route('eventos.index')->with('success', 'Evento actualizado');
        } catch (Throwable $e) {
            Log::error('Error en Eventos@update', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
            return back()->withInput()->withErrors(['app' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $evento = Evento::findOrFail($id);

        if (!auth()->user()->hasRole('superadmin')) {
            Gate::authorize('eliminar-evento', $evento);
        }

        $evento->delete();
        return redirect()->route('eventos.index')->with('success', 'Evento eliminado');
    }

    // Opcionales si existen en tus rutas:
    public function aprobar($id)
    {
        $evento = Evento::findOrFail($id);
        if (!auth()->user()->hasRole('superadmin')) {
            Gate::authorize('editar-evento', $evento);
        }
        $evento->estado = 'aprobado';
        $evento->save();

        return redirect()->route('eventos.index')->with('success', 'Evento aprobado');
    }

    public function cancelar($id)
    {
        $evento = Evento::findOrFail($id);
        if (!auth()->user()->hasRole('superadmin')) {
            Gate::authorize('editar-evento', $evento);
        }
        $evento->estado = 'pendiente';
        $evento->save();

        return redirect()->route('eventos.index')->with('success', 'Evento marcado como pendiente');
    }
}
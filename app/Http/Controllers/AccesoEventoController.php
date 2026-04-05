<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\AccesoEvento;
use App\Models\InvitacionEvento;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccesoEventoController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    // MÉTODO 1: Lista de eventos para control de acceso
    public function index()
    {
        $user = auth()->user();

        // Superadmin ve todos los eventos
        if ($user->hasRole('superadmin')) {
            $eventos = Evento::orderBy('fecha_evento', 'desc')->get();
        } else {
            // Admin y subadmin solo ven sus eventos
            $personaId = $user->persona_id;
            $eventos = Evento::whereHas('personas', function($q) use ($personaId) {
                $q->where('persona_id', $personaId)
                  ->whereIn('role', ['admin_evento', 'subadmin_evento']);
            })->orderBy('fecha_evento', 'desc')->get();
        }

        return view('accesos.index', compact('eventos'));
    }

    // MÉTODO 2: Página de control de acceso de UN evento específico
    public function show(Evento $evento)
    {
        // Verificar permisos
        $this->authorize('manageGuests', $evento);

        // Obtener estadísticas del evento
        $totalInvitados = InvitacionEvento::where('evento_id', $evento->id)
            ->where('confirmado', 1)
            ->sum('cantidad');

        $dentroAhora = $this->contarDentroAhora($evento->id);

        return view('accesos.show', compact('evento', 'totalInvitados', 'dentroAhora'));
    }

    // MÉTODO 3: Escanear QR y registrar entrada/salida
    public function escanearQr(Request $request, Evento $evento)
{
    $request->validate([
        'token' => 'required|string'
    ]);

    // ========== CAMBIO IMPORTANTE: BUSCAR POR token_acceso ==========
    // Antes buscábamos por 'token' (el de confirmación)
    // Ahora buscamos por 'token_acceso' (el del QR de ingreso)
    $invitacion = InvitacionEvento::where('token_acceso', $request->token)
        ->where('evento_id', $evento->id)
        ->where('confirmado', 1)
        ->first();

    if (!$invitacion) {
        \Log::warning('[ACCESO] Token no encontrado', [
            'token_recibido' => $request->token,
            'evento_id' => $evento->id
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Invitación no válida o no confirmada.'
        ], 400);
    }

    // Obtener persona asociada
    $persona = \App\Models\Persona::where('email', $invitacion->email)->first();

    if (!$persona) {
        return response()->json([
            'success' => false,
            'message' => 'Persona no encontrada en el sistema.'
        ], 400);
    }

    // Determinar tipo de acceso (entrada o salida)
    $ultimoAcceso = \App\Models\AccesoEvento::where('invitacion_id', $invitacion->id)
        ->where('evento_id', $evento->id)
        ->orderBy('fecha_hora', 'desc')
        ->first();

    $tipo = (!$ultimoAcceso || $ultimoAcceso->tipo === 'salida') ? 'entrada' : 'salida';

    // Registrar el acceso
    \App\Models\AccesoEvento::create([
        'evento_id' => $evento->id,
        'invitacion_id' => $invitacion->id,
        'persona_id' => $persona->id,
        'tipo' => $tipo,
        'fecha_hora' => now(),
    ]);

    \Log::info('[ACCESO] Registrado correctamente', [
        'tipo' => $tipo,
        'persona' => $persona->nombre . ' ' . $persona->apellido,
        'evento_id' => $evento->id
    ]);

    // Calcular personas adentro AHORA
    $dentroAhora = \App\Models\AccesoEvento::from('accesos_evento as a1')
        ->join(\DB::raw('(select invitacion_id, MAX(fecha_hora) as ultima_fecha 
                          from accesos_evento 
                          where evento_id = ' . $evento->id . ' and tipo = "entrada" 
                          group by invitacion_id) as ult'), function($join) {
            $join->on('a1.invitacion_id', '=', 'ult.invitacion_id')
                 ->on('a1.fecha_hora', '=', 'ult.ultima_fecha');
        })
        ->whereNotExists(function($query) {
            $query->select(\DB::raw(1))
                  ->from('accesos_evento as a2')
                  ->whereColumn('a2.invitacion_id', 'a1.invitacion_id')
                  ->where('a2.tipo', 'salida')
                  ->whereColumn('a2.fecha_hora', '>', 'a1.fecha_hora');
        })
        ->count();

    return response()->json([
        'success' => true,
        'tipo' => $tipo,
        'persona' => [
            'nombre' => $persona->nombre,
            'apellido' => $persona->apellido,
            'email' => $persona->email,
        ],
        'dentro_ahora' => $dentroAhora,
    ]);
}

    // MÉTODO 4: Ingreso manual por DNI
    // MÉTODO 4: Ingreso manual por DNI
public function ingresoManual(Request $request, Evento $evento)
{
    $this->authorize('manageGuests', $evento);

    $request->validate([
        'dni' => 'required|string',
    ]);

    // Buscar persona por DNI
    $persona = Persona::where('dni', $request->dni)->first();

    if (!$persona) {
        return back()->withErrors(['dni' => 'No se encontró una persona con ese DNI.']);
    }

    // Buscar invitación por email de esa persona
    $invitacion = InvitacionEvento::where('email', $persona->email)
        ->where('evento_id', $evento->id)
        ->where('confirmado', 1)
        ->first();

    if (!$invitacion) {
        return back()->withErrors(['dni' => 'Esta persona no tiene una invitación confirmada para este evento.']);
    }

    // ========== CORRECCIÓN 1: FILTRAR POR EVENTO_ID ==========
    // Verificar estado actual SOLO EN ESTE EVENTO
    $ultimoAcceso = AccesoEvento::where('invitacion_id', $invitacion->id)
        ->where('evento_id', $evento->id)  // ← AGREGADO: filtrar por este evento
        ->orderBy('fecha_hora', 'desc')
        ->first();

    // Determinar tipo de acceso
    if (!$ultimoAcceso || $ultimoAcceso->tipo === 'salida') {
        $tipo = 'entrada';
    } else {
        $tipo = 'salida';
    }

    // Registrar acceso
    AccesoEvento::create([
        'evento_id' => $evento->id,
        'invitacion_id' => $invitacion->id,
        'tipo' => $tipo,
        'metodo' => 'manual',
        'registrado_por' => auth()->id(),
    ]);

    Log::info('[ACCESO MANUAL] Registrado', [
        'tipo' => $tipo,
        'persona' => $persona->nombre . ' ' . $persona->apellido,
        'dni' => $persona->dni,
        'evento_id' => $evento->id
    ]);

    // Calcular nuevos contadores
    $dentroAhora = $this->contarDentroAhora($evento->id);
    
    $totalInvitados = InvitacionEvento::where('evento_id', $evento->id)
        ->where('confirmado', 1)
        ->sum('cantidad');
    
    $faltantes = $totalInvitados - $dentroAhora;

    // ========== CORRECCIÓN 2: DEVOLVER JSON PARA AJAX ==========
    // Si la petición es AJAX, devolver JSON (para actualizar contadores)
    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'tipo' => $tipo,
            'persona' => [
                'nombre' => $persona->nombre,
                'apellido' => $persona->apellido,
                'dni' => $persona->dni,
            ],
            'dentro_ahora' => $dentroAhora,
            'total_invitados' => $totalInvitados,
            'faltantes' => $faltantes,
        ]);
    }

    // Si NO es AJAX, redirigir con mensaje
    return back()->with('status', "✅ {$tipo} registrada para {$persona->nombre} {$persona->apellido}");
}

    // MÉTODO 5: Obtener historial de accesos (para mostrar en tabla)
    public function historial(Evento $evento)
    {
        $this->authorize('manageGuests', $evento);

        $accesos = AccesoEvento::where('evento_id', $evento->id)
            ->with(['invitacion', 'persona'])
            ->orderBy('fecha_hora', 'desc')
            ->paginate(50);

        return view('accesos.historial', compact('evento', 'accesos'));
    }

    // MÉTODO AUXILIAR: Contar cuántas personas están adentro AHORA
    private function contarDentroAhora($eventoId)
    {
        // Subconsulta: última entrada de cada invitación
        $ultimasEntradas = DB::table('accesos_evento')
            ->select('invitacion_id', DB::raw('MAX(fecha_hora) as ultima_fecha'))
            ->where('evento_id', $eventoId)
            ->where('tipo', 'entrada')
            ->groupBy('invitacion_id');

        // Contar solo las que NO tienen una salida posterior
        $dentro = DB::table('accesos_evento as a1')
            ->joinSub($ultimasEntradas, 'ult', function($join) {
                $join->on('a1.invitacion_id', '=', 'ult.invitacion_id')
                     ->on('a1.fecha_hora', '=', 'ult.ultima_fecha');
            })
            ->whereNotExists(function($query) use ($eventoId) {
                $query->select(DB::raw(1))
                    ->from('accesos_evento as a2')
                    ->whereColumn('a2.invitacion_id', 'a1.invitacion_id')
                    ->where('a2.tipo', 'salida')
                    ->whereColumn('a2.fecha_hora', '>', 'a1.fecha_hora');
            })
            ->count();

        return $dentro;
    }
}
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
            'token' => 'required|string',
            'personas' => 'nullable|integer|min:1',
        ]);

        // Buscar invitación por token_acceso
        $invitacion = InvitacionEvento::where('token_acceso', $request->token)
            ->where('evento_id', $evento->id)
            ->where('confirmado', 1)
            ->first();

        if (!$invitacion) {
            Log::warning('[ACCESO] Token no encontrado', [
                'token_recibido' => $request->token,
                'evento_id' => $evento->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invitación no válida o no confirmada.'
            ], 400);
        }

        // Obtener persona asociada
        $persona = Persona::where('email', $invitacion->email)->first();

        if (!$persona) {
            return response()->json([
                'success' => false,
                'message' => 'Persona no encontrada en el sistema.'
            ], 400);
        }

        // Determinar tipo de acceso
        $ultimoAcceso = AccesoEvento::where('invitacion_id', $invitacion->id)
            ->where('evento_id', $evento->id)
            ->orderBy('fecha_hora', 'desc')
            ->first();

        // Calcular cuántas personas de esta invitación están adentro AHORA
        $personasDentro = 0;
        if ($ultimoAcceso && $ultimoAcceso->tipo === 'entrada') {
            $personasDentro = $ultimoAcceso->personas_ingresadas ?? 1;
        }

        // Cantidad de personas que quieren ingresar/salir (por defecto 1)
        $personasMovimiento = $request->input('personas', 1);

        $tipo = (!$ultimoAcceso || $ultimoAcceso->tipo === 'salida') ? 'entrada' : 'salida';

        // ========== VALIDAR SEGÚN EL TIPO ==========
        if ($tipo === 'entrada') {
            // Validar que no excedan el límite
            if (($personasDentro + $personasMovimiento) > $invitacion->cantidad) {
                $disponibles = $invitacion->cantidad - $personasDentro;
                return response()->json([
                    'success' => false,
                    'message' => "Solo quedan {$disponibles} lugares disponibles de {$invitacion->cantidad}."
                ], 400);
            }
        } else {
            // Validar que no salgan más de los que están adentro
            if ($personasMovimiento > $personasDentro) {
                return response()->json([
                    'success' => false,
                    'message' => "Solo hay {$personasDentro} personas adentro. No pueden salir {$personasMovimiento}."
                ], 400);
            }
        }

        // Registrar el acceso
        AccesoEvento::create([
            'evento_id' => $evento->id,
            'invitacion_id' => $invitacion->id,
            'persona_id' => $persona->id,
            'tipo' => $tipo,
            'personas_ingresadas' => $personasMovimiento,
            'fecha_hora' => now(),
        ]);

        Log::info('[ACCESO QR] Registrado', [
            'tipo' => $tipo,
            'persona' => $persona->nombre . ' ' . $persona->apellido,
            'personas_movimiento' => $personasMovimiento,
            'personas_dentro_ahora' => $tipo === 'entrada' ? $personasDentro + $personasMovimiento : $personasDentro - $personasMovimiento,
            'evento_id' => $evento->id
        ]);

        // Calcular total de personas adentro del evento
        $dentroAhora = AccesoEvento::from('accesos_evento as a1')
            ->join(DB::raw('(select invitacion_id, MAX(fecha_hora) as ultima_fecha 
                              from accesos_evento 
                              where evento_id = ' . $evento->id . ' and tipo = "entrada" 
                              group by invitacion_id) as ult'), function($join) {
                $join->on('a1.invitacion_id', '=', 'ult.invitacion_id')
                     ->on('a1.fecha_hora', '=', 'ult.ultima_fecha');
            })
            ->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('accesos_evento as a2')
                      ->whereColumn('a2.invitacion_id', 'a1.invitacion_id')
                      ->where('a2.tipo', 'salida')
                      ->whereColumn('a2.fecha_hora', '>', 'a1.fecha_hora');
            })
            ->count();

        return response()->json([
            'success' => true,
            'tipo' => $tipo,
            'personas_movimiento' => $personasMovimiento,
            'personas_dentro_invitacion' => $tipo === 'entrada' ? $personasDentro + $personasMovimiento : $personasDentro - $personasMovimiento,
            'total_permitido' => $invitacion->cantidad,
            'persona' => [
                'nombre' => $persona->nombre,
                'apellido' => $persona->apellido,
                'email' => $persona->email,
                'dni' => $persona->dni,
            ],
            'dentro_ahora' => $dentroAhora,
        ]);
    }

    // MÉTODO 4: Ingreso manual por DNI
    public function ingresoManual(Request $request, Evento $evento)
    {
        $this->authorize('manageGuests', $evento);

        $request->validate([
            'dni' => 'required|string',
            'personas' => 'nullable|integer|min:1',
        ]);

        // Buscar persona por DNI
        $persona = Persona::where('dni', $request->dni)->first();

        if (!$persona) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una persona con ese DNI.'
            ], 400);
        }

        // Buscar invitación por email de esa persona EN ESTE EVENTO
        $invitacion = InvitacionEvento::where('email', $persona->email)
            ->where('evento_id', $evento->id)
            ->where('confirmado', 1)
            ->first();

        if (!$invitacion) {
            return response()->json([
                'success' => false,
                'message' => 'Esta persona no tiene una invitación confirmada para este evento.'
            ], 400);
        }

        // Determinar tipo de acceso
        $ultimoAcceso = AccesoEvento::where('invitacion_id', $invitacion->id)
            ->where('evento_id', $evento->id)
            ->orderBy('fecha_hora', 'desc')
            ->first();

        // Calcular cuántas personas de esta invitación están adentro AHORA
        $personasDentro = 0;
        if ($ultimoAcceso && $ultimoAcceso->tipo === 'entrada') {
            $personasDentro = $ultimoAcceso->personas_ingresadas ?? 1;
        }

        // Cantidad de personas que quieren ingresar/salir (por defecto 1)
        $personasMovimiento = $request->input('personas', 1);

        $tipo = (!$ultimoAcceso || $ultimoAcceso->tipo === 'salida') ? 'entrada' : 'salida';

        // ========== VALIDAR SEGÚN EL TIPO ==========
        if ($tipo === 'entrada') {
            // Validar que no excedan el límite
            if (($personasDentro + $personasMovimiento) > $invitacion->cantidad) {
                $disponibles = $invitacion->cantidad - $personasDentro;
                return response()->json([
                    'success' => false,
                    'message' => "Solo quedan {$disponibles} lugares disponibles de {$invitacion->cantidad}."
                ], 400);
            }
        } else {
            // Validar que no salgan más de los que están adentro
            if ($personasMovimiento > $personasDentro) {
                return response()->json([
                    'success' => false,
                    'message' => "Solo hay {$personasDentro} personas adentro. No pueden salir {$personasMovimiento}."
                ], 400);
            }
        }

        // Registrar el acceso
        AccesoEvento::create([
            'evento_id' => $evento->id,
            'invitacion_id' => $invitacion->id,
            'persona_id' => $persona->id,
            'tipo' => $tipo,
            'personas_ingresadas' => $personasMovimiento,
            'fecha_hora' => now(),
        ]);

        Log::info('[ACCESO MANUAL] Registrado', [
            'tipo' => $tipo,
            'persona' => $persona->nombre . ' ' . $persona->apellido,
            'dni' => $persona->dni,
            'personas_movimiento' => $personasMovimiento,
            'personas_dentro_ahora' => $tipo === 'entrada' ? $personasDentro + $personasMovimiento : $personasDentro - $personasMovimiento,
            'evento_id' => $evento->id
        ]);

        // Calcular cuántos están adentro AHORA
        $dentroAhora = AccesoEvento::from('accesos_evento as a1')
            ->join(DB::raw('(select invitacion_id, MAX(fecha_hora) as ultima_fecha 
                              from accesos_evento 
                              where evento_id = ' . $evento->id . ' and tipo = "entrada" 
                              group by invitacion_id) as ult'), function($join) {
                $join->on('a1.invitacion_id', '=', 'ult.invitacion_id')
                     ->on('a1.fecha_hora', '=', 'ult.ultima_fecha');
            })
            ->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('accesos_evento as a2')
                      ->whereColumn('a2.invitacion_id', 'a1.invitacion_id')
                      ->where('a2.tipo', 'salida')
                      ->whereColumn('a2.fecha_hora', '>', 'a1.fecha_hora');
            })
            ->count();

        return response()->json([
            'success' => true,
            'tipo' => $tipo,
            'personas_movimiento' => $personasMovimiento,
            'personas_dentro_invitacion' => $tipo === 'entrada' ? $personasDentro + $personasMovimiento : $personasDentro - $personasMovimiento,
            'total_permitido' => $invitacion->cantidad,
            'persona' => [
                'nombre' => $persona->nombre,
                'apellido' => $persona->apellido,
                'dni' => $persona->dni,
            ],
            'dentro_ahora' => $dentroAhora,
        ]);
    }

    // MÉTODO 5: Consultar información de invitación (sin registrar nada)
    public function consultarInvitacion(Request $request, Evento $evento)
    {
        $request->validate([
            'token' => 'nullable|string',
            'dni' => 'nullable|string',
        ]);

        $invitacion = null;
        $persona = null;

        // Buscar por token (QR)
        if ($request->has('token') && $request->token) {
            $invitacion = InvitacionEvento::where('token_acceso', $request->token)
                ->where('evento_id', $evento->id)
                ->where('confirmado', 1)
                ->first();

            if ($invitacion) {
                $persona = Persona::where('email', $invitacion->email)->first();
            }
        }
        // Buscar por DNI
        elseif ($request->has('dni') && $request->dni) {
            $persona = Persona::where('dni', $request->dni)->first();

            if ($persona) {
                $invitacion = InvitacionEvento::where('email', $persona->email)
                    ->where('evento_id', $evento->id)
                    ->where('confirmado', 1)
                    ->first();
            }
        }

        if (!$invitacion || !$persona) {
            return response()->json([
                'success' => false,
                'message' => 'Invitación no encontrada o no confirmada.'
            ], 400);
        }

        // Buscar último acceso
        $ultimoAcceso = AccesoEvento::where('invitacion_id', $invitacion->id)
            ->where('evento_id', $evento->id)
            ->orderBy('fecha_hora', 'desc')
            ->first();

        // Calcular cuántas personas están adentro AHORA
        $personasDentro = 0;
        if ($ultimoAcceso && $ultimoAcceso->tipo === 'entrada') {
            $personasDentro = $ultimoAcceso->personas_ingresadas ?? 1;
        }

        // Calcular disponibles
        $disponibles = $invitacion->cantidad - $personasDentro;

        // Determinar tipo sugerido
        $tipoSugerido = (!$ultimoAcceso || $ultimoAcceso->tipo === 'salida') ? 'entrada' : 'salida';

        return response()->json([
            'success' => true,
            'invitacion_id' => $invitacion->id,
            'persona' => [
                'nombre' => $persona->nombre,
                'apellido' => $persona->apellido,
                'dni' => $persona->dni,
                'email' => $persona->email,
            ],
            'evento' => [
                'nombre' => $evento->nombre,
            ],
            'cupos' => [
                'total' => $invitacion->cantidad,
                'ingresados' => $personasDentro,
                'disponibles' => $disponibles,
            ],
            'tipo_sugerido' => $tipoSugerido,
        ]);
    }

    // MÉTODO 6: Confirmar y registrar el acceso
    public function confirmarAcceso(Request $request, Evento $evento)
    {
        $request->validate([
            'invitacion_id' => 'required|integer',
            'tipo' => 'required|in:entrada,salida',
            'personas' => 'required|integer|min:1',
        ]);

        // Buscar invitación
        $invitacion = InvitacionEvento::where('id', $request->invitacion_id)
            ->where('evento_id', $evento->id)
            ->where('confirmado', 1)
            ->first();

        if (!$invitacion) {
            return response()->json([
                'success' => false,
                'message' => 'Invitación no válida.'
            ], 400);
        }

        // Buscar persona
        $persona = Persona::where('email', $invitacion->email)->first();

        if (!$persona) {
            return response()->json([
                'success' => false,
                'message' => 'Persona no encontrada.'
            ], 400);
        }

        // Buscar último acceso
        $ultimoAcceso = AccesoEvento::where('invitacion_id', $invitacion->id)
            ->where('evento_id', $evento->id)
            ->orderBy('fecha_hora', 'desc')
            ->first();

        // Calcular cuántas personas están adentro AHORA
        $personasDentro = 0;
        if ($ultimoAcceso && $ultimoAcceso->tipo === 'entrada') {
            $personasDentro = $ultimoAcceso->personas_ingresadas ?? 1;
        }

        $personasMovimiento = $request->personas;
        $tipo = $request->tipo;

        // ========== VALIDACIONES ==========
        if ($tipo === 'entrada') {
            // Validar que no excedan el límite
            if (($personasDentro + $personasMovimiento) > $invitacion->cantidad) {
                $disponibles = $invitacion->cantidad - $personasDentro;
                return response()->json([
                    'success' => false,
                    'message' => "NO TIENE SUFICIENTES CUPOS PARA ESA CANTIDAD DE PERSONAS. Disponibles: {$disponibles}"
                ], 400);
            }
        } else {
            // Validar que no salgan más de los que están adentro
            if ($personasMovimiento > $personasDentro) {
                return response()->json([
                    'success' => false,
                    'message' => "Solo hay {$personasDentro} personas adentro. No pueden salir {$personasMovimiento}."
                ], 400);
            }
        }

        // ========== REGISTRAR EL ACCESO ==========
        AccesoEvento::create([
            'evento_id' => $evento->id,
            'invitacion_id' => $invitacion->id,
            'persona_id' => $persona->id,
            'tipo' => $tipo,
            'personas_ingresadas' => $personasMovimiento,
            'fecha_hora' => now(),
        ]);

        Log::info('[ACCESO CONFIRMADO] Registrado', [
            'tipo' => $tipo,
            'persona' => $persona->nombre . ' ' . $persona->apellido,
            'personas_movimiento' => $personasMovimiento,
            'evento_id' => $evento->id
        ]);

        // Calcular nuevos valores
        $personasDentroNuevo = $tipo === 'entrada' ? $personasDentro + $personasMovimiento : $personasDentro - $personasMovimiento;
        $disponiblesNuevo = $invitacion->cantidad - $personasDentroNuevo;

        // Calcular total del evento
        $dentroAhora = $this->contarDentroAhora($evento->id);

        return response()->json([
            'success' => true,
            'message' => 'GRACIAS',
            'cupos' => [
                'total' => $invitacion->cantidad,
                'ingresados' => $personasDentroNuevo,
                'disponibles' => $disponiblesNuevo,
            ],
            'dentro_ahora' => $dentroAhora,
        ]);
    }

    // MÉTODO 7: Obtener historial de accesos (para mostrar en tabla)
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
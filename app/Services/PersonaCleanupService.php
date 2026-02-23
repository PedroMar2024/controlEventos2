<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PersonaCleanupService
{
    public static function cleanup($personas, $eventoId)
    {
        $eliminados = [];

        foreach ($personas as $persona) {
            $user = $persona->user;

            $quedaEnEventos = DB::table('event_persona_roles')
                ->where('persona_id', $persona->id)
                ->where('evento_id', '!=', $eventoId) // <<< CLAVE!!!
                ->whereIn('role', ['admin', 'subadmin', 'invitado'])
                ->exists();

            Log::debug('DESTROY: Persona check', [
                'persona_id' => $persona->id,
                'quedaEnEventos' => $quedaEnEventos,
                'user_id' => $user ? $user->id : null,
            ]);

            if (!$quedaEnEventos) {
                $persona->delete();
                Log::debug('DESTROY: Persona eliminada', ['persona_id' => $persona->id]);

                if ($user) {
                    Log::debug('DESTROY: Roles antes de borrar', [
                        'roles' => $user->getRoleNames()
                    ]);
                    $user->syncRoles([]);
                    $rows = DB::table('model_has_roles')
                        ->where('model_id', $user->id)
                        ->where('model_type', get_class($user))
                        ->delete();
                    Log::debug('DESTROY: Model_has_roles elimina filas', ['user_id' => $user->id, 'rows_deleted' => $rows]);

                    $deleted = DB::table('users')->where('id', $user->id)->delete();
                    Log::debug('DESTROY: Usuario eliminado', [
                        'user_id' => $user->id,
                        'deleted_rows' => $deleted,
                        'usuarios_restantes' => DB::table('users')->where('id', $user->id)->count()
                    ]);
                }
                $eliminados[] = $persona->nombre . ' ' . $persona->apellido;
            }
        }

        return $eliminados;
    }
}
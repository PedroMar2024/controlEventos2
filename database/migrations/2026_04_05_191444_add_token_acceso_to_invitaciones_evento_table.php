<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invitaciones_evento', function (Blueprint $table) {
            // Agregar nueva columna para el token de acceso al evento
            $table->string('token_acceso', 100)->nullable()->after('token');
            
            // Crear índice para búsquedas rápidas por token_acceso
            $table->index('token_acceso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitaciones_evento', function (Blueprint $table) {
            // Eliminar el índice primero
            $table->dropIndex(['token_acceso']);
            
            // Eliminar la columna
            $table->dropColumn('token_acceso');
        });
    }
};
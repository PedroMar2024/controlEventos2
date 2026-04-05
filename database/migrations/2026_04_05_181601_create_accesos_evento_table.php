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
        Schema::create('accesos_evento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained('eventos')->onDelete('cascade');
            $table->foreignId('invitacion_id')->constrained('invitaciones_evento')->onDelete('cascade');
            $table->enum('tipo', ['entrada', 'salida']);
            $table->timestamp('fecha_hora')->useCurrent();
            $table->enum('metodo', ['qr', 'manual'])->default('qr');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index(['evento_id', 'fecha_hora']);
            $table->index(['invitacion_id', 'fecha_hora']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accesos_evento');
    }
};
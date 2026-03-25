<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('ticket_solicitudes', function (Blueprint $table) {
        $table->id();

        // Relación con evento
        $table->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();

        // Datos de la persona que pide (por si no es user registrado)
        $table->string('dni', 32);
        $table->string('nombre', 255);
        $table->string('apellido', 255);
        $table->string('email', 255);

        // Cantidad de entradas pedidas
        $table->unsignedInteger('cantidad')->default(1);

        // Estado de la solicitud ("pendiente", "confirmada", "enviada", "cancelada")
        $table->enum('estado', ['pendiente', 'confirmada', 'enviada', 'cancelada'])->default('pendiente');
        
        // Códigos QR asociados (puede ser json para varios)
        $table->json('codigos_qr')->nullable();

        // Timestamps para auditoría
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets_eventos');
    }
};

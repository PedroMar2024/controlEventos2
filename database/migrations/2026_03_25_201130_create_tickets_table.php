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
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();
        $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
        $table->unsignedBigInteger('ticket_solicitud_id')->nullable(); // solo por auditoría, si querés saber a qué solicitud pertenece
        $table->string('codigo_qr')->unique();
        $table->enum('estado', ['pendiente', 'enviado', 'usado', 'anulado'])->default('pendiente');
        $table->timestamps();

        // FK extra, no obligatoria:
        $table->foreign('ticket_solicitud_id')->references('id')->on('ticket_solicitudes')->nullOnDelete();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

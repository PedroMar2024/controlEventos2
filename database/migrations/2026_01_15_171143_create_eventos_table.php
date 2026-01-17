<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('admin_persona_id')->nullable()->constrained('personas')->nullOnDelete();

            // FECHA única del evento y HORAS separadas
            $table->date('fecha_evento')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_cierre')->nullable();

            $table->string('ubicacion')->nullable();
            $table->string('localidad')->nullable();
            $table->string('provincia')->nullable();

            $table->integer('capacidad')->nullable();

            $table->enum('estado', ['pendiente','aprobado','finalizado'])->default('pendiente');

            $table->text('descripcion')->nullable();

            $table->decimal('precio_evento', 10, 2)->nullable();

            $table->boolean('publico')->default(false);
            $table->boolean('reingreso')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};

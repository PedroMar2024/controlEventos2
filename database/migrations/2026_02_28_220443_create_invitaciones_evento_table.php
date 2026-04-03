<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvitacionesEventoTable extends Migration
{
    public function up()
    {
        Schema::create('invitaciones_evento', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evento_id');
            $table->string('email');
            $table->integer('cantidad')->default(1); // <<--- NUEVO: cantidad de personas habilitadas por esa invitación
            $table->string('token', 100)->nullable(); // Se usará para QR
            $table->boolean('enviada')->default(false); // Marcar si ya se envió la invitación
            $table->timestamp('fecha_envio')->nullable(); // Fecha-hora de envío del mail
            $table->timestamp('fecha_confirmacion')->nullable(); // Cuando respondió
            $table->boolean('datos_completados')->default(false); // Si llenó datos extra
            $table->timestamps();

            $table->foreign('evento_id')->references('id')->on('eventos')->onDelete('cascade');
            $table->unique(['evento_id', 'email']); // No pueden cargarse dos veces mismo email para un evento
        });
    }

    public function down()
    {
        Schema::dropIfExists('invitaciones_evento');
    }
}
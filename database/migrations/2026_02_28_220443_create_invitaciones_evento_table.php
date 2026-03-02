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
            $table->string('token', 100)->nullable();
            $table->boolean('enviada')->default(false);
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_confirmacion')->nullable();
            $table->boolean('datos_completados')->default(false);
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
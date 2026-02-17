<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');                // Ahora obligatorio
            $table->string('apellido');              // Ahora obligatorio
            $table->string('dni')->unique();         // Obligatorio y único
            $table->string('telefono')->nullable();  // Opcional
            $table->string('email')->unique();       // Obligatorio y único
            $table->string('direccion')->nullable(); // Opcional
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
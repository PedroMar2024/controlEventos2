<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evento_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->decimal('precio', 10, 2)->default(0);
            $table->unsignedInteger('cupo')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['evento_id', 'nombre']); // evita duplicados de nombre por evento
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_tickets');
    }
};
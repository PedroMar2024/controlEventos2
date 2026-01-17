<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_persona_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->string('role'); // admin | subadmin | invitado
            $table->timestamps();

            $table->unique(['evento_id','persona_id','role'], 'event_persona_role_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_persona_roles');
    }
};
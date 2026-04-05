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
        Schema::table('accesos_evento', function (Blueprint $table) {
            $table->integer('personas_ingresadas')->default(1)->after('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accesos_evento', function (Blueprint $table) {
            $table->dropColumn('personas_ingresadas');
        });
    }
};

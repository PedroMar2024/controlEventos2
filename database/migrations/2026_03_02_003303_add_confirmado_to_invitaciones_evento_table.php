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
    Schema::table('invitaciones_evento', function (Blueprint $table) {
        $table->boolean('confirmado')->nullable()->default(null)->after('datos_completados');
    });
}
    /**
     * Reverse the migrations.
     */
    public function down()
{
    Schema::table('invitaciones_evento', function (Blueprint $table) {
        $table->dropColumn('confirmado');
    });
}
};

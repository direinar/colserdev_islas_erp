<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ARL (administradora de riesgos laborales) de la seguridad social del empleado.
     */
    public function up(): void
    {
        Schema::table('nomina_empleados', function (Blueprint $table) {
            $table->string('arl')->nullable()->after('fondo_cesantias');
        });
    }

    public function down(): void
    {
        Schema::table('nomina_empleados', function (Blueprint $table) {
            $table->dropColumn('arl');
        });
    }
};

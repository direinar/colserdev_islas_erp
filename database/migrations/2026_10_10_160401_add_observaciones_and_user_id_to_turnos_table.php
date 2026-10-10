<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - observaciones: el campo OBSERVACIONES de la planilla existía en pantalla pero no se guardaba.
     * - user_id: quién creó la planilla; un islero solo puede modificar las suyas.
     */
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('observaciones');
        });
    }
};

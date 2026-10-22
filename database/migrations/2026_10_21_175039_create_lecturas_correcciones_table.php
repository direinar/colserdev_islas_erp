<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fase 2 y 3 de la planilla por mangueras:
     * - Corrección de una lectura final que el turno siguiente ya tomó como inicial: se
     *   actualiza también la inicial de ese turno y queda el registro de quién y cuándo.
     * - Cambio de contador autorizado por el administrador: lectura del contador viejo al
     *   retirarlo y del nuevo al instalarlo, en la manguera del turno donde ocurrió.
     */
    public function up(): void
    {
        Schema::table('turno_surtidores', function (Blueprint $table) {
            $table->decimal('contador_retiro', 12, 3)->nullable()->after('lectura_final');
            $table->decimal('contador_instalacion', 12, 3)->nullable()->after('contador_retiro');
        });

        Schema::create('lecturas_correcciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->foreignId('turno_siguiente_id')->nullable()->constrained('turnos')->nullOnDelete();
            $table->string('manguera', 20);
            $table->decimal('valor_anterior', 12, 3)->nullable();
            $table->decimal('valor_nuevo', 12, 3)->nullable();
            $table->decimal('contador_retiro', 12, 3)->nullable();
            $table->decimal('contador_instalacion', 12, 3)->nullable();
            $table->string('observacion', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturas_correcciones');

        Schema::table('turno_surtidores', function (Blueprint $table) {
            $table->dropColumn(['contador_retiro', 'contador_instalacion']);
        });
    }
};

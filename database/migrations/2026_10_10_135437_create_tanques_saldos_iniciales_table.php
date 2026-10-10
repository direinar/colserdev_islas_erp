<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Galones en el tanque al iniciar el día de la fecha de corte. El control de
     * tanques arranca desde aquí y acumula compras y ventas a partir de esa fecha.
     */
    public function up(): void
    {
        Schema::create('tanques_saldos_iniciales', function (Blueprint $table) {
            $table->id();
            $table->string('combustible', 20)->unique();
            $table->date('fecha_corte');
            $table->decimal('galones', 14, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanques_saldos_iniciales');
    }
};

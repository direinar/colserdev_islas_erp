<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos', function (Blueprint $table): void {
            $table->id();
            $table->date('fecha');
            $table->unsignedSmallInteger('numero_turno');
            $table->string('nombre_vendedor')->nullable();
            $table->string('revisado_por')->nullable();
            $table->decimal('precio_corriente', 12, 2)->default(0);
            $table->decimal('precio_acpm', 12, 2)->default(0);
            $table->json('ventas_surtidor')->nullable();
            $table->json('lecturas')->nullable();
            $table->json('consignaciones')->nullable();
            $table->json('cartera')->nullable();
            $table->decimal('tc_datafono_1', 14, 2)->default(0);
            $table->decimal('tc_datafono_2', 14, 2)->default(0);
            $table->decimal('tc_datafono_3', 14, 2)->default(0);
            $table->decimal('transferencias_bancolombia', 14, 2)->default(0);
            $table->decimal('gasolina_eds', 14, 2)->default(0);
            $table->decimal('puntos_redimidos', 14, 2)->default(0);
            $table->json('urea_lubricantes')->nullable();
            $table->json('recaudos_anticipos')->nullable();
            $table->json('varios')->nullable();
            $table->json('recaudos_administracion')->nullable();
            $table->decimal('traslado_sobrante', 14, 2)->default(0);
            $table->decimal('traslado_faltante', 14, 2)->default(0);
            $table->decimal('total_venta_iapropiada', 16, 2)->default(0);
            $table->decimal('total_venta_lectura', 16, 2)->default(0);
            $table->decimal('total_urea_sin_iva', 16, 2)->default(0);
            $table->decimal('total_urea_iva', 16, 2)->default(0);
            $table->decimal('subtotal_ingresos', 16, 2)->default(0);
            $table->decimal('total_recaudos', 16, 2)->default(0);
            $table->decimal('total_recibido', 16, 2)->default(0);
            $table->decimal('faltante_sobrante_iapropiada', 16, 2)->default(0);
            $table->decimal('faltante_sobrante_lectura', 16, 2)->default(0);
            $table->timestamps();

            $table->unique(['fecha', 'numero_turno']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos');
    }
};

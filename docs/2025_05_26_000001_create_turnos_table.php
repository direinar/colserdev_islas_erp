<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->integer('numero_turno');
            $table->string('nombre_vendedor')->nullable();
            $table->string('revisado_por')->nullable();
            $table->timestamps();
        });

        Schema::create('ventas_surtidor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('surtidor'); // Ej: "SURTIDOR 1 CTE"
            $table->string('tipo_combustible'); // CTE, ACPM
            $table->decimal('galones', 12, 3)->default(0);
            $table->decimal('valor', 16, 2)->default(0);
        });

        Schema::create('lecturas_surtidor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('manguera'); // "PLUS O1", "ACPM O3", etc.
            $table->string('tipo_combustible'); // CTE o ACPM
            $table->decimal('lectura_inicial', 16, 3)->default(0);
            $table->decimal('lectura_final', 16, 3)->default(0);
        });

        Schema::create('consignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('numero')->nullable();
            $table->decimal('valor', 16, 2)->default(0);
            $table->decimal('descuento', 16, 2)->default(0);
        });

        Schema::create('cartera_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('numero_factura')->nullable();
            $table->string('cliente')->nullable();
            $table->decimal('valor', 16, 2)->default(0);
        });

        Schema::create('ventas_urea_lubricantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->decimal('cantidad', 10, 2)->default(0);
            $table->string('producto');
            $table->decimal('valor_sin_iva', 16, 2)->default(0);
            $table->decimal('iva', 16, 2)->default(0);
        });

        Schema::create('medios_pago_electronicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->decimal('tc_datafono_1', 16, 2)->default(0);
            $table->decimal('tc_datafono_2', 16, 2)->default(0);
            $table->decimal('tc_datafono_3', 16, 2)->default(0);
            $table->decimal('transferencias_bancolombia', 16, 2)->default(0);
            $table->decimal('gasolina_eds', 16, 2)->default(0);
            $table->decimal('puntos_redimidos', 16, 2)->default(0);
        });

        Schema::create('recaudos_anticipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('cliente');
            $table->decimal('valor', 16, 2)->default(0);
        });

        Schema::create('varios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('concepto');
            $table->decimal('valor', 16, 2)->default(0);
        });

        Schema::create('recaudos_administracion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('banco_caja');
            $table->string('cliente');
            $table->decimal('valor', 16, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recaudos_administracion');
        Schema::dropIfExists('varios');
        Schema::dropIfExists('recaudos_anticipos');
        Schema::dropIfExists('medios_pago_electronicos');
        Schema::dropIfExists('ventas_urea_lubricantes');
        Schema::dropIfExists('cartera_credito');
        Schema::dropIfExists('consignaciones');
        Schema::dropIfExists('lecturas_surtidor');
        Schema::dropIfExists('ventas_surtidor');
        Schema::dropIfExists('turnos');
    }
};

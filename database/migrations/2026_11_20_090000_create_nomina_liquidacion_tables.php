<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nómina del mes (hoja NOMINA), contabilización, liquidación de contratos, control de
     * vacaciones y de dotación (plantilla "NOMINA PARA PROGRAMACION").
     */
    public function up(): void
    {
        Schema::create('nomina_liquidaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->foreignId('parametro_liquidacion_id')->nullable()->constrained('nomina_parametros_liquidacion')->nullOnDelete();
            $table->string('estado', 20)->default('borrador');
            $table->text('observaciones')->nullable();
            $table->string('aprobada_por')->nullable();
            $table->timestamp('aprobada_at')->nullable();
            $table->timestamps();

            $table->unique(['anio', 'mes'], 'nomina_liq_anio_mes_unique');
        });

        Schema::create('nomina_liquidacion_empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidacion_id')->constrained('nomina_liquidaciones')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('nomina_empleados')->restrictOnDelete();

            // Datos del empleado al liquidar.
            $table->decimal('salario_basico', 14, 2);
            $table->boolean('recibe_auxilio_transporte')->default(false);
            $table->boolean('aplica_recargos')->default(false);

            // Novedades del mes.
            $table->unsignedTinyInteger('dias_trabajados')->default(30);
            $table->unsignedTinyInteger('dias_vacaciones_disfrutadas')->default(0);
            $table->unsignedTinyInteger('dias_vacaciones_dinero')->default(0);
            $table->decimal('horas_nocturnas', 8, 2)->default(0);
            $table->decimal('horas_festivas', 8, 2)->default(0);
            $table->decimal('cesantias', 14, 2)->default(0);
            $table->decimal('intereses_cesantias', 14, 2)->default(0);
            $table->decimal('prima', 14, 2)->default(0);
            $table->decimal('anticipos', 14, 2)->default(0);
            $table->decimal('faltantes', 14, 2)->default(0);
            $table->string('medio_pago', 60)->nullable();
            $table->string('observaciones', 500)->nullable();

            // Valores calculados (NominaLiquidacionService::calcular).
            foreach ([
                'basico', 'vacaciones', 'recargo_nocturno', 'recargo_festivo', 'base_aportes', 'auxilio_transporte',
                'total_devengado', 'salud', 'pension', 'total_deducciones', 'neto_pagar',
                'aporte_pension', 'aporte_salud', 'aporte_riesgos', 'aporte_caja',
                'provision_cesantias', 'provision_intereses', 'provision_vacaciones', 'provision_prima', 'costo_total',
            ] as $columna) {
                $table->decimal($columna, 14, 2)->default(0);
            }
            $table->timestamps();

            $table->unique(['liquidacion_id', 'empleado_id'], 'nomina_liq_emp_unique');
        });

        Schema::create('nomina_cuentas_contables', function (Blueprint $table) {
            $table->id();
            $table->string('concepto', 40)->unique();
            $table->string('nombre', 80);
            $table->string('cuenta', 20)->nullable();
            $table->string('naturaleza', 10);
            $table->unsignedTinyInteger('orden')->default(0);
            $table->timestamps();
        });

        // Cuentas de la hoja CONTABILIZACION de la plantilla.
        $ahora = now();
        DB::table('nomina_cuentas_contables')->insert(array_map(fn (array $fila): array => [
            'concepto' => $fila[0], 'nombre' => $fila[1], 'cuenta' => $fila[2], 'naturaleza' => $fila[3], 'orden' => $fila[4],
            'created_at' => $ahora, 'updated_at' => $ahora,
        ], [
            ['basico', 'BÁSICO', '510506', 'debito', 1],
            ['vacaciones', 'VACACIONES', '510539', 'debito', 2],
            ['recargos', 'RECARGOS NOCT Y FESTIVOS', '510515', 'debito', 3],
            ['auxilio_transporte', 'AUXILIO DE TRANSPORTE', '510527', 'debito', 4],
            ['cesantias', 'CESANTÍAS', '510530', 'debito', 5],
            ['intereses_cesantias', 'INTERESES S/CESANTÍAS', '510533', 'debito', 6],
            ['prima', 'PRIMA', '510536', 'debito', 7],
            ['salud_pension', 'SALUD Y PENSIÓN', '425020', 'credito', 8],
            ['anticipos_faltantes', 'ANTICIPOS Y FALTANTES', '11050501', 'credito', 9],
            ['bancos', 'BANCOS (NETO A PAGAR)', null, 'credito', 10],
        ]));

        Schema::create('nomina_liquidaciones_contrato', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('nomina_empleados')->restrictOnDelete();
            $table->date('fecha_ingreso');
            $table->date('fecha_retiro');
            $table->string('motivo_retiro', 150);
            $table->decimal('salario_base', 14, 2);
            $table->unsignedSmallInteger('dias_anio');
            $table->unsignedSmallInteger('dias_semestre');
            $table->unsignedInteger('dias_contrato');
            $table->decimal('sueldo_pendiente', 14, 2)->default(0);
            $table->decimal('recargos_nocturnos', 14, 2)->default(0);
            $table->decimal('recargos_festivos', 14, 2)->default(0);
            $table->decimal('auxilio_transporte', 14, 2)->default(0);
            $table->decimal('cesantias', 14, 2)->default(0);
            $table->decimal('intereses_cesantias', 14, 2)->default(0);
            $table->decimal('prima', 14, 2)->default(0);
            $table->decimal('vacaciones', 14, 2)->default(0);
            $table->decimal('salud', 14, 2)->default(0);
            $table->decimal('pension', 14, 2)->default(0);
            $table->decimal('otras_deducciones', 14, 2)->default(0);
            $table->decimal('neto_pagar', 14, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->string('elaborada_por')->nullable();
            $table->timestamps();
        });

        Schema::create('nomina_vacaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('nomina_empleados')->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->unsignedTinyInteger('dias_disfrutados')->default(0);
            $table->unsignedTinyInteger('dias_dinero')->default(0);
            $table->string('periodo', 40)->nullable();
            $table->string('observaciones', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('nomina_dotaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('nomina_empleados')->cascadeOnDelete();
            $table->date('fecha_entrega');
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('entrega');
            $table->string('elementos', 500);
            $table->string('observaciones', 500)->nullable();
            $table->timestamps();

            $table->unique(['empleado_id', 'anio', 'entrega'], 'nomina_dot_emp_entrega_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_dotaciones');
        Schema::dropIfExists('nomina_vacaciones');
        Schema::dropIfExists('nomina_liquidaciones_contrato');
        Schema::dropIfExists('nomina_cuentas_contables');
        Schema::dropIfExists('nomina_liquidacion_empleados');
        Schema::dropIfExists('nomina_liquidaciones');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comprobante contable del conteo (plantilla "COMPROBANTE DE CONTABILIDAD DE CONTEO"):
     * tabla de cuentas, conceptos, terceros y NIT, y la cuenta de cada línea de la
     * consignación en bancos y otras aplicaciones.
     */
    public function up(): void
    {
        Schema::create('conteo_cuentas', function (Blueprint $table) {
            $table->id();
            $table->string('cuenta', 20);
            $table->string('concepto', 150);
            $table->string('tercero', 120)->nullable();
            $table->string('nit', 30)->nullable();
            $table->timestamps();

            $table->unique(['cuenta', 'concepto'], 'conteo_cuentas_cuenta_concepto_unique');
        });

        $ahora = now();
        DB::table('conteo_cuentas')->insert(array_map(fn (array $fila): array => [
            'cuenta' => $fila[0], 'concepto' => $fila[1], 'tercero' => $fila[2], 'nit' => $fila[3],
            'created_at' => $ahora, 'updated_at' => $ahora,
        ], [
            ['23050501', 'ABONO A CUENTA', 'CHEVRON PETROLEUM COMPANY', '860005223'],
            ['210507', 'CUOTA CREDITO FINAGRO BBVA', 'BANCO BBVA', '860003020'],
            ['210512', 'CUOTA CREDITO ROTATIVO BBVA', 'BANCO BBVA', '860003020'],
            ['210514', 'CUOTA CREDITO COMERCIAL BBVA', 'BANCO BBVA', '860003020'],
            ['210516', 'ABONO TC BBVA', 'BANCO BBVA', '860003020'],
            ['53051501', 'COMISIONES BANCARIAS CREDITO', 'BANCO BBVA', '860003020'],
            ['530520', 'INTERESES CREDITO', 'BANCO BBVA', '860003020'],
            ['11100502', 'CONSIGNACION CTA CORRIENTE DAVIVIENDA', 'GENERAL', null],
            ['11100507', 'CONSIGNACION CTA CORRIENTE BBVA', 'GENERAL', null],
            ['11100510', 'CONSIGNACION CTA AHORROS BANCOLOMBIA', 'GENERAL', null],
        ]));

        Schema::table('conteo_aplicaciones', function (Blueprint $table) {
            $table->foreignId('conteo_cuenta_id')->nullable()->after('conteo_tercero_id')->constrained('conteo_cuentas')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conteo_aplicaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conteo_cuenta_id');
        });
        Schema::dropIfExists('conteo_cuentas');
    }
};

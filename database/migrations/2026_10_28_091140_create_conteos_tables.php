<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulo CONTEO (plantilla "PROGRAMACION CONTEO"): cuadre del efectivo de la EDS cuando se
     * decida hacerlo. Cada conteo tiene billetes y moneda, retiros antes del cierre, la
     * numeración de consignaciones internas (sus valores salen de las planillas de turno),
     * gastos, consignaciones en bancos y otras aplicaciones, y el control de la moneda.
     */
    public function up(): void
    {
        Schema::create('conteos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('numero')->unique();
            $table->date('fecha');
            $table->decimal('moneda', 15, 2)->default(0);
            // Solo el primer conteo: punto de partida que en los demás arrastra el conteo anterior.
            $table->unsignedBigInteger('consignacion_anterior')->nullable();
            $table->decimal('saldo_moneda_inicial', 15, 2)->nullable();
            // Último No. de consignación interna de este conteo: el siguiente arranca después de él.
            $table->unsignedBigInteger('ultima_consignacion')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('conteo_billetes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conteo_id')->constrained('conteos')->cascadeOnDelete();
            $table->string('denominacion', 20);
            $table->decimal('valor', 15, 2)->default(0);
            $table->timestamps();
        });

        // Retiros antes del cierre, gastos del conteo y retiros de moneda: filas concepto/valor.
        Schema::create('conteo_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conteo_id')->constrained('conteos')->cascadeOnDelete();
            $table->string('tipo', 20);
            $table->string('nombre', 120)->nullable();
            $table->string('concepto', 200)->nullable();
            $table->decimal('valor', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['conteo_id', 'tipo']);
        });

        Schema::create('conteo_consignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conteo_id')->constrained('conteos')->cascadeOnDelete();
            $table->unsignedBigInteger('numero');
            $table->decimal('valor', 15, 2)->default(0);
            // Planilla de turno de donde salió el valor (null si el número no está en ninguna).
            $table->foreignId('turno_id')->nullable()->constrained('turnos')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('conteo_destinos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->timestamps();
        });

        Schema::create('conteo_terceros', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->string('tipo', 10)->default('banco');
            $table->timestamps();
        });

        Schema::create('conteo_aplicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conteo_id')->constrained('conteos')->cascadeOnDelete();
            $table->foreignId('conteo_destino_id')->nullable()->constrained('conteo_destinos')->restrictOnDelete();
            $table->foreignId('conteo_tercero_id')->nullable()->constrained('conteo_terceros')->restrictOnDelete();
            $table->decimal('valor', 15, 2)->default(0);
            $table->timestamps();
        });

        // Tablas de conceptos y de terceros de la plantilla.
        $ahora = now();
        DB::table('conteo_destinos')->insert(array_map(fn (string $nombre): array => ['nombre' => $nombre, 'created_at' => $ahora, 'updated_at' => $ahora], [
            'Abono a Mayorista',
            'Consigna en Cuenta ByH De:',
            'Abono a Crédito - CAPITAL',
            'Abono a Crédito - INTERESES',
            'Abono a OTROS - Crédito',
        ]));
        DB::table('conteo_terceros')->insert(array_map(fn (array $tercero): array => $tercero + ['created_at' => $ahora, 'updated_at' => $ahora], [
            ['nombre' => 'Chevron Petroleum Company', 'tipo' => 'tercero'],
            ['nombre' => 'DAVIVIENDA CTA CTE', 'tipo' => 'banco'],
            ['nombre' => 'DAVIVIENDA AHORROS', 'tipo' => 'banco'],
            ['nombre' => 'BANCO BBVA CTA CTE', 'tipo' => 'banco'],
            ['nombre' => 'BANCOLOMBIA AHORROS', 'tipo' => 'banco'],
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('conteo_aplicaciones');
        Schema::dropIfExists('conteo_terceros');
        Schema::dropIfExists('conteo_destinos');
        Schema::dropIfExists('conteo_consignaciones');
        Schema::dropIfExists('conteo_movimientos');
        Schema::dropIfExists('conteo_billetes');
        Schema::dropIfExists('conteos');
    }
};

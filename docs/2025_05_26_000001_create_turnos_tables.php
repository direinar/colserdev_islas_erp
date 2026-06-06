<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Roles de usuario ─────────────────────────────────────────
        // Añadir campo 'rol' a la tabla users existente de Laravel
        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', ['islero', 'administrador'])->default('islero')->after('email');
        });

        // ── Tabla de precios de combustibles ─────────────────────────
        // Solo un registro activo a la vez. El administrador lo actualiza.
        Schema::create('precios_combustible', function (Blueprint $table) {
            $table->id();
            $table->decimal('precio_corriente', 12, 2);   // CTE por galón
            $table->decimal('precio_acpm',      12, 2);   // ACPM por galón
            $table->date('vigente_desde');
            $table->boolean('activo')->default(true);
            $table->foreignId('creado_por')->constrained('users');
            $table->timestamps();
        });

        // ── Catálogo de productos (urea y lubricantes) ───────────────
        // El administrador mantiene este catálogo.
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('valor_sin_iva', 14, 2);
            $table->decimal('iva',           14, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ── Planillas de turno ────────────────────────────────────────
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->unsignedSmallInteger('numero_turno');
            $table->foreignId('vendedor_id')->constrained('users');
            $table->foreignId('precio_combustible_id')->constrained('precios_combustible');

            // Revisión: solo el administrador puede marcar como revisado
            $table->boolean('revisado')->default(false);
            $table->foreignId('revisado_por')->nullable()->constrained('users');
            $table->timestamp('revisado_at')->nullable();

            // Estado: borrador → cerrado → revisado
            $table->enum('estado', ['borrador', 'cerrado', 'revisado'])->default('borrador');

            $table->timestamps();

            $table->unique(['fecha', 'numero_turno']);
        });

        // ── Ventas según cierres de IAPROPIADA ───────────────────────
        // Solo 'galones' es editable (islero). 'valor' = galones × precio.
        Schema::create('ventas_surtidor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('surtidor');
            $table->enum('tipo_combustible', ['CTE', 'ACPM']);
            $table->decimal('galones', 12, 3)->default(0);
            // valor se calcula: galones × precio del turno
        });

        // ── Lecturas electrónicas de surtidores ──────────────────────
        // 'lectura_inicial' viene de la lectura_final del turno anterior (bloqueado).
        // 'lectura_final'  la transcribe el islero.
        // 'galones' y 'valor' se calculan automáticamente.
        Schema::create('lecturas_surtidor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('manguera');
            $table->enum('tipo_combustible', ['CTE', 'ACPM']);
            $table->decimal('lectura_inicial', 16, 3)->default(0);  // arrastrado automáticamente
            $table->decimal('lectura_final',   16, 3)->default(0);  // único campo editable aquí
        });

        // ── Ventas de urea y lubricantes ─────────────────────────────
        // 'cantidad' es editable. 'producto_id' es un desplegable.
        // nombre, valor_sin_iva, iva y total se jalan del catálogo → bloqueados.
        Schema::create('ventas_urea_lubricante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->unsignedTinyInteger('orden')->default(1);
            $table->foreignId('producto_id')->nullable()->constrained('productos');
            $table->decimal('cantidad',     10, 2)->default(0);
            // nombre_producto, valor_sin_iva, iva se copian snapshot al guardar
            $table->string('nombre_producto')->nullable();
            $table->decimal('valor_sin_iva', 14, 2)->default(0);
            $table->decimal('iva',           14, 2)->default(0);
            // total = cantidad × (valor_sin_iva + iva) — calculado
        });

        // ── Consignaciones ───────────────────────────────────────────
        Schema::create('consignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('numero_consignacion')->nullable();
            $table->decimal('valor',     14, 2)->default(0);
            $table->decimal('descuento', 14, 2)->default(0);
        });

        // ── Cartera / Crédito directo ─────────────────────────────────
        Schema::create('cartera_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('numero_factura')->nullable();
            $table->string('cliente');
            $table->decimal('valor', 14, 2)->default(0);
        });

        // ── Medios de pago electrónicos (TC, QR, Nequi…) ─────────────
        // Un solo registro por turno.
        Schema::create('medios_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->unique()->constrained('turnos')->cascadeOnDelete();
            $table->decimal('tc_datafono_1',             14, 2)->default(0);
            $table->decimal('tc_datafono_2',             14, 2)->default(0);
            $table->decimal('tc_datafono_3',             14, 2)->default(0);
            $table->decimal('transferencias_bancolombia', 14, 2)->default(0);
            $table->decimal('gasolina_eds',              14, 2)->default(0);
            $table->decimal('puntos_redimidos',          14, 2)->default(0);
        });

        // ── Recaudos, anticipos y prepagos por islas ─────────────────
        Schema::create('recaudos_anticipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('cliente');
            $table->decimal('valor', 14, 2)->default(0);
        });

        // ── Varios ───────────────────────────────────────────────────
        Schema::create('varios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('concepto');
            $table->decimal('valor', 14, 2)->default(0);
        });

        // ── Traslados (sobrante / faltante confirmados) ───────────────
        // Solo el administrador puede llenar esto.
        Schema::create('traslados_turno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->unique()->constrained('turnos')->cascadeOnDelete();
            $table->decimal('traslado_sobrante', 14, 2)->default(0);
            $table->decimal('traslado_faltante', 14, 2)->default(0);
            $table->foreignId('registrado_por')->constrained('users');
            $table->timestamps();
        });

        // ── Recaudos por administración (módulo separado) ─────────────
        Schema::create('recaudos_administracion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->cascadeOnDelete();
            $table->string('banco_caja');
            $table->string('cliente');
            $table->decimal('valor', 14, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recaudos_administracion');
        Schema::dropIfExists('traslados_turno');
        Schema::dropIfExists('varios');
        Schema::dropIfExists('recaudos_anticipos');
        Schema::dropIfExists('medios_pago');
        Schema::dropIfExists('cartera_credito');
        Schema::dropIfExists('ventas_urea_lubricante');
        Schema::dropIfExists('lecturas_surtidor');
        Schema::dropIfExists('ventas_surtidor');
        Schema::dropIfExists('turnos');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('precios_combustible');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rol');
        });
    }
};

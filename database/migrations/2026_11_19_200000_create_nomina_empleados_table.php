<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_empleados', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_documento', 5)->default('CC');
            $table->string('numero_documento', 20)->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('nombre_corto', 30);
            $table->string('telefono', 30)->nullable();
            $table->string('correo')->nullable();
            $table->string('direccion')->nullable();

            $table->string('cargo');
            $table->date('fecha_ingreso');
            $table->string('tipo_contrato', 20);
            $table->date('fecha_vencimiento_contrato')->nullable();
            $table->decimal('salario_basico', 14, 2);
            $table->boolean('recibe_auxilio_transporte')->default(false);
            $table->boolean('aplica_recargos')->default(false);

            $table->string('eps')->nullable();
            $table->string('fondo_pension')->nullable();
            $table->string('fondo_cesantias')->nullable();

            $table->string('forma_pago', 20)->default('transferencia');
            $table->string('banco')->nullable();
            $table->string('tipo_cuenta', 20)->nullable();
            $table->string('numero_cuenta', 30)->nullable();

            $table->boolean('activo')->default(true);
            $table->date('fecha_retiro')->nullable();
            $table->string('motivo_retiro')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_empleados');
    }
};

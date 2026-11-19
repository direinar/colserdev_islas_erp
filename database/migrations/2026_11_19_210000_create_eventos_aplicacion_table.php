<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fallas y eventos de la aplicación (Auditoría del administrador): errores del sistema,
     * acciones de los usuarios e ingresos. Las fallas y advertencias se gestionan con un
     * estado (pendiente, en revisión, resuelto, descartado) y una observación.
     */
    public function up(): void
    {
        Schema::create('eventos_aplicacion', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->string('nivel', 20);
            $table->string('modulo', 40)->nullable();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->json('detalle')->nullable();
            $table->string('metodo', 10)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('ruta', 150)->nullable();
            $table->unsignedSmallInteger('estado_http')->nullable();
            $table->string('ip', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // La misma falla repetida suma ocurrencias en vez de crear otro registro.
            $table->string('huella', 64)->nullable();
            $table->unsignedInteger('ocurrencias')->default(1);
            $table->timestamp('ultima_vez')->nullable();
            $table->string('estado', 20);
            $table->foreignId('gestionado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('gestionado_at')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index('huella');
            $table->index(['estado', 'nivel']);
            $table->index('ultima_vez');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_aplicacion');
    }
};

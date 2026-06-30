<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compras_lubricantes', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('nombre', 150)->default('');
            $table->string('no_fc', 40)->default('');
            $table->decimal('unidades', 12, 3)->default(0);
            $table->decimal('valor_unitario', 15, 2)->default(0);
            $table->decimal('vr_sin_iva', 15, 0)->default(0);
            $table->decimal('iva', 15, 0)->default(0);
            $table->decimal('total', 15, 0)->default(0);
            $table->timestamps();

            $table->index(['fecha']);
            $table->index(['no_fc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras_lubricantes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('factura', 60);
            $table->decimal('vr_total_fra', 15, 2)->default(0);
            $table->decimal('gasolina', 12, 3)->default(0);
            $table->decimal('acpm', 12, 3)->default(0);
            $table->decimal('total', 12, 3)->default(0);
            $table->decimal('distribucion_gasolina', 15, 2)->default(0);
            $table->decimal('distribucion_acpm', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['fecha']);
            $table->index(['factura']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingresos_tanques', function (Blueprint $table): void {
            $table->id();
            $table->date('fecha')->unique();
            $table->decimal('ingreso_corriente', 14, 3)->default(0);
            $table->decimal('ingreso_acpm', 14, 3)->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingresos_tanques');
    }
};

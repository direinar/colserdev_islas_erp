<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('planilla_cartera_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_id')->constrained()->cascadeOnDelete();
            $table->string('cliente', 180);
            $table->decimal('valor', 14, 2)->default(0);
            $table->decimal('abono_1', 14, 2)->default(0);
            $table->decimal('abono_2', 14, 2)->default(0);
            $table->decimal('saldo', 14, 2)->default(0);
            $table->unsignedInteger('orden')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planilla_cartera_items');
    }
};

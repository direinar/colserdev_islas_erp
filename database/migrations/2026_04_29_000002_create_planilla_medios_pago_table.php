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
        Schema::create('planilla_medios_pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_id')->constrained()->cascadeOnDelete();
            $table->string('medio', 120);
            $table->decimal('valor', 14, 2)->default(0);
            $table->unsignedInteger('orden')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planilla_medios_pago');
    }
};

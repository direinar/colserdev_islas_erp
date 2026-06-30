<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('anticipos_bimestrales', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('bimestre')->default(1);
            $table->string('periodo', 60)->default('');
            $table->string('mes', 30)->default('');
            $table->decimal('galones', 12, 3)->default(0);
            $table->decimal('valor_intermediacion', 15, 2)->default(0);
            $table->decimal('pesos', 15, 0)->default(0);
            $table->timestamps();

            $table->index(['bimestre']);
            $table->index(['periodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anticipos_bimestrales');
    }
};

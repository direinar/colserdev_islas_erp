<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Medición física de los tanques que el jefe de patios toma cada noche:
     * lectura de la vara y galones según la tabla de aforo, por combustible.
     * Un registro por fecha; es lo único que se digita en el control de tanques.
     */
    public function up(): void
    {
        Schema::create('aforos_tanques', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->decimal('vara_corriente', 10, 2)->nullable();
            $table->decimal('tabla_corriente', 14, 3)->nullable();
            $table->decimal('vara_acpm', 10, 2)->nullable();
            $table->decimal('tabla_acpm', 14, 3)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aforos_tanques');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La urea automotriz se vende por litros: la cantidad de canastilla admite decimales.
     */
    public function up(): void
    {
        Schema::table('turno_lubricantes', function (Blueprint $table) {
            $table->decimal('cantidad', 12, 3)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('turno_lubricantes', function (Blueprint $table) {
            $table->unsignedInteger('cantidad')->default(0)->change();
        });
    }
};

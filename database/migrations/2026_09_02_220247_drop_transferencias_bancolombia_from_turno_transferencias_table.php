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
        Schema::table('turno_transferencias', function (Blueprint $table) {
            $table->dropColumn(['valor', 'total']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turno_transferencias', function (Blueprint $table) {
            $table->decimal('valor', 15, 2)->default(0)->after('turno_id');
            $table->decimal('total', 15, 2)->default(0)->after('puntos_redimidos');
        });
    }
};

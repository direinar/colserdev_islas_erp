<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turnos', function (Blueprint $table): void {
            $table->boolean('anulada')->default(false)->after('revisado');
            $table->string('anulada_por')->nullable()->after('anulada');
            $table->timestamp('anulada_at')->nullable()->after('anulada_por');
            $table->string('motivo_anulacion', 500)->nullable()->after('anulada_at');
            $table->index(['anulada', 'fecha']);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->string('document')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('turnos', function (Blueprint $table): void {
            $table->dropIndex(['anulada', 'fecha']);
            $table->dropColumn(['anulada', 'anulada_por', 'anulada_at', 'motivo_anulacion']);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->string('document')->nullable(false)->change();
        });
    }
};

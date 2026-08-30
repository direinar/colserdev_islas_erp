<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras_lubricantes', function (Blueprint $table) {
            $table->foreignId('vendedor_id')
                ->nullable()
                ->after('fecha')
                ->constrained('vendedores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if ($this->foreignKeyExists('compras_lubricantes', 'compras_lubricantes_vendedor_id_foreign')) {
            Schema::table('compras_lubricantes', function (Blueprint $table) {
                $table->dropForeign(['vendedor_id']);
            });
        }

        if (Schema::hasColumn('compras_lubricantes', 'vendedor_id')) {
            Schema::table('compras_lubricantes', function (Blueprint $table) {
                $table->dropColumn('vendedor_id');
            });
        }
    }

    /**
     * Check whether a named foreign key constraint exists, since renaming
     * columns across migrations can leave stale/orphaned index names that
     * make an unconditional dropForeign() fail on refresh.
     */
    private function foreignKeyExists(string $table, string $constraint): bool
    {
        // information_schema.TABLE_CONSTRAINTS is MySQL-specific; other drivers
        // don't need this stale-constraint check.
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
};

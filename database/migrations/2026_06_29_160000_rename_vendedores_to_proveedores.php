<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendedores') && ! Schema::hasTable('proveedores')) {
            Schema::rename('vendedores', 'proveedores');
        }

        if (Schema::hasTable('compras_lubricantes') && Schema::hasColumn('compras_lubricantes', 'vendedor_id')) {
            $this->dropForeignKeyOnColumn('compras_lubricantes', 'vendedor_id');

            Schema::table('compras_lubricantes', function (Blueprint $table) {
                $table->renameColumn('vendedor_id', 'proveedor_id');
            });

            $this->dropStaleIndexesOnColumn('compras_lubricantes', 'proveedor_id');

            Schema::table('compras_lubricantes', function (Blueprint $table) {
                $table->foreign('proveedor_id')
                    ->references('id')
                    ->on('proveedores')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Renombrar la tabla referenciada primero: la FK de abajo debe apuntar
        // a 'vendedores', que solo existe después de este rename.
        if (Schema::hasTable('proveedores') && ! Schema::hasTable('vendedores')) {
            Schema::rename('proveedores', 'vendedores');
        }

        if (Schema::hasTable('compras_lubricantes') && Schema::hasColumn('compras_lubricantes', 'proveedor_id')) {
            $this->dropForeignKeyOnColumn('compras_lubricantes', 'proveedor_id');

            Schema::table('compras_lubricantes', function (Blueprint $table) {
                $table->renameColumn('proveedor_id', 'vendedor_id');
            });

            $this->dropStaleIndexesOnColumn('compras_lubricantes', 'vendedor_id');

            Schema::table('compras_lubricantes', function (Blueprint $table) {
                $table->foreign('vendedor_id')
                    ->references('id')
                    ->on('vendedores')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Drop whatever foreign key constraint actually exists on this column,
     * regardless of its name. MySQL does not rename the constraint when a
     * column is renamed, so relying on Laravel's {table}_{column}_foreign
     * naming convention here is unreliable across repeated up/down cycles.
     */
    private function dropForeignKeyOnColumn(string $table, string $column): void
    {
        // information_schema.KEY_COLUMN_USAGE is MySQL-specific; other drivers
        // (e.g. SQLite in tests) don't need this stale-constraint cleanup.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        foreach ($constraints as $constraint) {
            DB::statement("alter table `{$table}` drop foreign key `{$constraint}`");
        }
    }

    /**
     * Renaming a column in MySQL does not rename indexes built on it, so a
     * plain (non-foreign-key) index from a previous rename cycle can be left
     * pointing at the new column under an unrelated name. That stale index
     * then collides with the fresh FK index this migration creates. Drop
     * every index on the column except the primary key before recreating it.
     */
    private function dropStaleIndexesOnColumn(string $table, string $column): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $indexes = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->where('INDEX_NAME', '!=', 'PRIMARY')
            ->distinct()
            ->pluck('INDEX_NAME');

        foreach ($indexes as $index) {
            DB::statement("alter table `{$table}` drop index `{$index}`");
        }
    }
};

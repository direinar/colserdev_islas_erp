<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('turno_qr_pagos', 'total')) {
            Schema::table('turno_qr_pagos', function (Blueprint $table): void {
                $table->decimal('total', 15, 2)->default(0)->after('valor');
            });
        }

        DB::table('turno_qr_pagos')
            ->distinct()
            ->pluck('turno_id')
            ->each(function (int|string $turnoId): void {
                $total = DB::table('turno_qr_pagos')
                    ->where('turno_id', $turnoId)
                    ->sum('valor');

                DB::table('turno_qr_pagos')
                    ->where('turno_id', $turnoId)
                    ->update(['total' => $total]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turno_qr_pagos', function (Blueprint $table) {
            $table->dropColumn('total');
        });
    }
};

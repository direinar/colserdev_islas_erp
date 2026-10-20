<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dígito de verificación (D.V.) del NIT. Los clientes existentes se completan con el
     * dígito que calcula la DIAN para su número de documento.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('dv', 1)->nullable()->after('document');
        });

        DB::table('customers')->select(['id', 'document'])->orderBy('id')->each(function (object $customer): void {
            DB::table('customers')->where('id', $customer->id)->update([
                'dv' => Customer::digitoVerificacion((string) $customer->document),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('dv');
        });
    }
};

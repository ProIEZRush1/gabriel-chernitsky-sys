<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentas', function (Blueprint $table) {
            // Saldo a favor del arrendatario cuando sus cobros (auxiliar bancario)
            // superan lo facturado. Se recalcula junto con las mensualidades.
            $table->decimal('excedente', 14, 2)->default(0)->after('meses_adeudo');
        });
    }

    public function down(): void
    {
        Schema::table('rentas', function (Blueprint $table) {
            $table->dropColumn('excedente');
        });
    }
};

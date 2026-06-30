<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentas', function (Blueprint $table) {
            // ¿La renta causa IVA? Si es así se desglosa sobre el importe mensual.
            $table->boolean('tiene_iva')->default(false)->after('monto_mensual');
            // Tasa de IVA aplicable (México: 16% por defecto).
            $table->decimal('iva_tasa', 5, 2)->default(16)->after('tiene_iva');
        });
    }

    public function down(): void
    {
        Schema::table('rentas', function (Blueprint $table) {
            $table->dropColumn(['tiene_iva', 'iva_tasa']);
        });
    }
};

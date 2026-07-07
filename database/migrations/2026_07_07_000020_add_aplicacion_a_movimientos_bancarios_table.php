<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_bancarios', function (Blueprint $table) {
            // Detalle (mensualidad => monto) a donde se aplicó este cobro; permite
            // revertirlo con exactitud si el movimiento se edita o se elimina.
            $table->json('aplicado_detalle')->nullable()->after('renta_id');
            // Parte de este cobro que no alcanzó mensualidad y quedó como pago excedente.
            $table->decimal('excedente_aplicado', 14, 2)->default(0)->after('aplicado_detalle');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_bancarios', function (Blueprint $table) {
            $table->dropColumn(['aplicado_detalle', 'excedente_aplicado']);
        });
    }
};

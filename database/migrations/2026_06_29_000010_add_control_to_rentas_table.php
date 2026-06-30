<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentas', function (Blueprint $table) {
            // Vencimiento del contrato de renta (no de un pago puntual).
            $table->date('fecha_vencimiento_renta')->nullable()->after('fecha_inicio');
            // Días de gracia tras el día de pago antes de aplicar recargo
            // (define la fecha de vencimiento de pago de cada mensualidad).
            $table->unsignedTinyInteger('dias_gracia')->default(0)->after('dia_pago');
            // Recargo fijo por pago tardío, además del interés moratorio.
            $table->decimal('recargo_fijo', 14, 2)->default(0)->after('tasa_moratoria');
            // Aumentos de renta: porcentaje manual + inflación del periodo.
            $table->decimal('porcentaje_aumento', 5, 2)->default(0)->after('recargo_fijo');
            $table->decimal('inflacion_periodo', 5, 2)->default(0)->after('porcentaje_aumento');
            $table->date('fecha_ultimo_aumento')->nullable()->after('inflacion_periodo');
        });
    }

    public function down(): void
    {
        Schema::table('rentas', function (Blueprint $table) {
            $table->dropColumn([
                'fecha_vencimiento_renta',
                'dias_gracia',
                'recargo_fijo',
                'porcentaje_aumento',
                'inflacion_periodo',
                'fecha_ultimo_aumento',
            ]);
        });
    }
};

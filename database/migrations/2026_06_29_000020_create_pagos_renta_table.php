<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_renta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('renta_id')->constrained('rentas')->cascadeOnDelete();
            // Periodo (mensualidad) al que corresponde el pago, ej. "2026-06".
            $table->string('periodo', 7);
            // Renta del periodo (importe esperado).
            $table->decimal('monto', 14, 2)->default(0);
            // Vencimiento de la renta (día de pago) y vencimiento del pago (con gracia).
            $table->date('fecha_vencimiento_renta')->nullable();
            $table->date('fecha_vencimiento_pago')->nullable();
            // Recargo cobrado al momento de pagar (lo aplicado realmente).
            $table->decimal('recargo', 14, 2)->default(0);
            // Lo efectivamente pagado y cuándo.
            $table->decimal('monto_pagado', 14, 2)->default(0);
            $table->date('fecha_pago')->nullable();
            $table->string('estado')->default('pendiente'); // pendiente, parcial, pagado
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['renta_id', 'periodo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_renta');
    }
};

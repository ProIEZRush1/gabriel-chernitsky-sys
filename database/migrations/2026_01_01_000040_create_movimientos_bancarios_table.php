<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_bancarios', function (Blueprint $table) {
            $table->id();
            $table->string('auxiliar'); // nombre del auxiliar / proyecto / cuenta
            $table->string('tipo')->default('cobro'); // pago, transferencia, cobro, deposito, retiro
            $table->string('concepto');
            $table->decimal('monto', 14, 2)->default(0);
            $table->date('fecha')->nullable();
            $table->string('referencia')->nullable();
            $table->foreignId('renta_id')->nullable()->constrained('rentas')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_bancarios');
    }
};

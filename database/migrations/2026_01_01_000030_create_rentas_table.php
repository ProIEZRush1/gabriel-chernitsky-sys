<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propiedad_id')->nullable()->constrained('propiedades')->nullOnDelete();
            $table->string('inquilino');
            $table->decimal('monto_mensual', 14, 2)->default(0);
            $table->unsignedTinyInteger('dia_pago')->default(1);
            $table->date('fecha_inicio')->nullable();
            $table->string('estado_pago')->default('al_corriente'); // al_corriente, con_adeudo, pagada
            $table->decimal('tasa_moratoria', 5, 2)->default(0); // % mensual
            $table->unsignedSmallInteger('meses_adeudo')->default(0);
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentas');
    }
};

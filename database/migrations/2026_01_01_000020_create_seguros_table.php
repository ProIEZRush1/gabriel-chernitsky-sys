<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguros', function (Blueprint $table) {
            $table->id();
            $table->string('ramo')->default('inmueble'); // inmueble, auto, medico
            $table->string('asegurado');
            $table->string('beneficiario')->nullable();
            $table->string('aseguradora');
            $table->string('numero_poliza')->nullable();
            $table->string('agente_venta')->nullable();
            $table->decimal('suma_asegurada', 14, 2)->default(0);
            $table->decimal('prima', 14, 2)->default(0);
            $table->text('condiciones')->nullable();
            $table->date('vigencia_inicio')->nullable();
            $table->date('vigencia_fin')->nullable();
            $table->string('estado')->default('vigente'); // vigente, por_vencer, vencido, cancelado
            $table->foreignId('propiedad_id')->nullable()->constrained('propiedades')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguros');
    }
};

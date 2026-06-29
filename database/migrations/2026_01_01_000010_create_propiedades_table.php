<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propiedades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo')->default('departamento'); // casa, departamento, local, terreno, oficina
            $table->string('direccion');
            $table->string('ciudad')->nullable();
            $table->decimal('valor_comercial', 14, 2)->default(0);
            $table->string('estado')->default('disponible'); // disponible, rentada, mantenimiento
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propiedades');
    }
};

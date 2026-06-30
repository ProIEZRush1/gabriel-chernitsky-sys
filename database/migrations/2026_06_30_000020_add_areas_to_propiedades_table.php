<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propiedades', function (Blueprint $table) {
            // Áreas rentables del inmueble (Local A, Local B, Piso 1, etc.).
            // Se guarda como JSON: cada elemento tiene nombre, renta mensual y si
            // es el área principal (el inmueble que se renta como tal). La primera
            // siempre es la principal y no se puede quitar; las demás son ilimitadas.
            $table->json('areas')->nullable()->after('notas');
        });
    }

    public function down(): void
    {
        Schema::table('propiedades', function (Blueprint $table) {
            $table->dropColumn('areas');
        });
    }
};

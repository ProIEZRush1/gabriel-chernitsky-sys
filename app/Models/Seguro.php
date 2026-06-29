<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seguro extends Model
{
    use HasFactory;

    protected $table = 'seguros';

    protected $fillable = [
        'ramo',
        'asegurado',
        'beneficiario',
        'aseguradora',
        'numero_poliza',
        'agente_venta',
        'suma_asegurada',
        'prima',
        'condiciones',
        'vigencia_inicio',
        'vigencia_fin',
        'estado',
        'propiedad_id',
    ];

    protected $casts = [
        'suma_asegurada' => 'decimal:2',
        'prima' => 'decimal:2',
        'vigencia_inicio' => 'date',
        'vigencia_fin' => 'date',
    ];

    public function propiedad()
    {
        return $this->belongsTo(Propiedad::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoBancario extends Model
{
    use HasFactory;

    protected $table = 'movimientos_bancarios';

    protected $fillable = [
        'auxiliar',
        'tipo',
        'concepto',
        'monto',
        'fecha',
        'referencia',
        'renta_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date',
    ];

    public function renta()
    {
        return $this->belongsTo(Renta::class);
    }
}

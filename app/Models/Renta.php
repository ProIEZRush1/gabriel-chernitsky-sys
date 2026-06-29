<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Renta extends Model
{
    use HasFactory;

    protected $table = 'rentas';

    protected $fillable = [
        'propiedad_id',
        'inquilino',
        'monto_mensual',
        'dia_pago',
        'fecha_inicio',
        'estado_pago',
        'tasa_moratoria',
        'meses_adeudo',
        'notas',
    ];

    protected $casts = [
        'monto_mensual' => 'decimal:2',
        'tasa_moratoria' => 'decimal:2',
        'fecha_inicio' => 'date',
    ];

    protected $appends = ['interes_moratorio', 'total_adeudo'];

    public function propiedad()
    {
        return $this->belongsTo(Propiedad::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoBancario::class);
    }

    /**
     * Interés moratorio acumulado = monto * (tasa% ) * meses de adeudo.
     */
    protected function interesMoratorio(): Attribute
    {
        return Attribute::make(
            get: fn () => round(
                ((float) $this->monto_mensual) * ((float) $this->tasa_moratoria / 100) * (int) $this->meses_adeudo,
                2
            ),
        );
    }

    /**
     * Total adeudado = rentas vencidas + interés moratorio.
     */
    protected function totalAdeudo(): Attribute
    {
        return Attribute::make(
            get: fn () => round(
                ((float) $this->monto_mensual) * (int) $this->meses_adeudo + $this->interes_moratorio,
                2
            ),
        );
    }
}

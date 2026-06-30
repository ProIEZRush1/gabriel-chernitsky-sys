<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoRenta extends Model
{
    use HasFactory;

    protected $table = 'pagos_renta';

    protected $fillable = [
        'renta_id',
        'periodo',
        'monto',
        'fecha_vencimiento_renta',
        'fecha_vencimiento_pago',
        'recargo',
        'monto_pagado',
        'fecha_pago',
        'estado',
        'notas',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'recargo' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'fecha_vencimiento_renta' => 'date',
        'fecha_vencimiento_pago' => 'date',
        'fecha_pago' => 'date',
    ];

    protected $appends = [
        'meses_vencido',
        'recargo_vigente',
        'total_periodo',
        'saldo',
        'estado_calculado',
    ];

    public function renta()
    {
        return $this->belongsTo(Renta::class);
    }

    private function estaPagado(): bool
    {
        return $this->estado === 'pagado'
            || ((float) $this->monto_pagado) >= ((float) $this->monto + (float) $this->recargo_vigente);
    }

    /**
     * Meses completos vencidos desde la fecha límite de pago (0 si está al día o pagado).
     */
    protected function mesesVencido(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->estado === 'pagado') {
                return 0;
            }
            $limite = $this->fecha_vencimiento_pago ?? $this->fecha_vencimiento_renta;
            if (! $limite) {
                return 0;
            }
            $hoy = Carbon::today();
            if ($hoy->lessThanOrEqualTo($limite)) {
                return 0;
            }

            return (int) ceil($limite->diffInDays($hoy) / 30);
        });
    }

    /**
     * Recargo vigente: si ya se pagó, el recargo realmente cobrado; si está vencido,
     * recargo fijo + interés moratorio (tasa% mensual × meses vencidos).
     */
    protected function recargoVigente(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->estado === 'pagado') {
                return round((float) $this->recargo, 2);
            }

            $meses = $this->meses_vencido;
            if ($meses <= 0) {
                return 0.0;
            }

            $renta = $this->renta;
            $tasa = $renta ? (float) $renta->tasa_moratoria : 0;
            $fijo = $renta ? (float) $renta->recargo_fijo : 0;

            return round($fijo + ((float) $this->monto) * ($tasa / 100) * $meses, 2);
        });
    }

    /**
     * Total a cobrar del periodo = renta + recargo vigente.
     */
    protected function totalPeriodo(): Attribute
    {
        return Attribute::make(
            get: fn () => round((float) $this->monto + (float) $this->recargo_vigente, 2),
        );
    }

    /**
     * Saldo pendiente del periodo (nunca negativo).
     */
    protected function saldo(): Attribute
    {
        return Attribute::make(
            get: fn () => round(max(0, (float) $this->total_periodo - (float) $this->monto_pagado), 2),
        );
    }

    /**
     * Estado en vivo: pagado, parcial, vencido o pendiente.
     */
    protected function estadoCalculado(): Attribute
    {
        return Attribute::make(get: function () {
            if ($this->estaPagado()) {
                return 'pagado';
            }
            if (((float) $this->monto_pagado) > 0) {
                return 'parcial';
            }
            if ($this->meses_vencido > 0) {
                return 'vencido';
            }

            return 'pendiente';
        });
    }
}

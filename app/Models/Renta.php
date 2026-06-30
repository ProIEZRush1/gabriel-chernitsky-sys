<?php

namespace App\Models;

use Carbon\Carbon;
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
        'dias_gracia',
        'fecha_inicio',
        'fecha_vencimiento_renta',
        'estado_pago',
        'tasa_moratoria',
        'recargo_fijo',
        'porcentaje_aumento',
        'inflacion_periodo',
        'fecha_ultimo_aumento',
        'meses_adeudo',
        'notas',
    ];

    protected $casts = [
        'monto_mensual' => 'decimal:2',
        'tasa_moratoria' => 'decimal:2',
        'recargo_fijo' => 'decimal:2',
        'porcentaje_aumento' => 'decimal:2',
        'inflacion_periodo' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_vencimiento_renta' => 'date',
        'fecha_ultimo_aumento' => 'date',
    ];

    protected $appends = [
        'interes_moratorio',
        'total_adeudo',
        'total_facturado',
        'total_cobrado',
        'total_recargos',
        'saldo_cuenta',
        'periodos_vencidos',
        'porcentaje_aumento_total',
    ];

    public function propiedad()
    {
        return $this->belongsTo(Propiedad::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoBancario::class);
    }

    public function pagos()
    {
        return $this->hasMany(PagoRenta::class)->orderBy('periodo');
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
     * Total adeudado (modelo simple) = rentas vencidas + interés moratorio.
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

    /**
     * Porcentaje de aumento total a aplicar = aumento manual + inflación del periodo.
     */
    protected function porcentajeAumentoTotal(): Attribute
    {
        return Attribute::make(
            get: fn () => round((float) $this->porcentaje_aumento + (float) $this->inflacion_periodo, 2),
        );
    }

    // ----- Estado de cuenta basado en mensualidades (pagos_renta) -----

    private function pagosCargados()
    {
        return $this->relationLoaded('pagos') ? $this->pagos : $this->pagos()->get();
    }

    /**
     * Total facturado = suma de (renta + recargo vigente) de cada mensualidad.
     */
    protected function totalFacturado(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->pagosCargados()->sum(fn ($p) => (float) $p->total_periodo), 2),
        );
    }

    protected function totalCobrado(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->pagosCargados()->sum(fn ($p) => (float) $p->monto_pagado), 2),
        );
    }

    protected function totalRecargos(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->pagosCargados()->sum(fn ($p) => (float) $p->recargo_vigente), 2),
        );
    }

    protected function periodosVencidos(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->pagosCargados()->filter(fn ($p) => $p->estado_calculado === 'vencido')->count(),
        );
    }

    /**
     * Saldo (adeudo) del arrendatario. Usa las mensualidades cuando existen;
     * de lo contrario cae al modelo simple (meses de adeudo).
     */
    protected function saldoCuenta(): Attribute
    {
        return Attribute::make(get: function () {
            $pagos = $this->pagosCargados();
            if ($pagos->isEmpty()) {
                return $this->total_adeudo;
            }

            return round($pagos->sum(fn ($p) => (float) $p->saldo), 2);
        });
    }

    /**
     * Calcula las fechas de vencimiento (renta y pago) para un periodo "YYYY-MM".
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function fechasDePeriodo(string $periodo): array
    {
        [$anio, $mes] = array_map('intval', explode('-', $periodo));
        $dia = min((int) ($this->dia_pago ?: 1), Carbon::create($anio, $mes, 1)->daysInMonth);

        $vencimientoRenta = Carbon::create($anio, $mes, $dia)->startOfDay();
        $vencimientoPago = $vencimientoRenta->copy()->addDays((int) $this->dias_gracia);

        return [$vencimientoRenta, $vencimientoPago];
    }

    /**
     * Aplica un aumento de renta. Si no se especifica un porcentaje, usa el
     * configurado (aumento manual + inflación del periodo).
     */
    public function aplicarAumento(?float $porcentaje = null): float
    {
        $pct = $porcentaje ?? (float) $this->porcentaje_aumento_total;
        $nuevo = round(((float) $this->monto_mensual) * (1 + $pct / 100), 2);

        $this->monto_mensual = $nuevo;
        $this->fecha_ultimo_aumento = Carbon::today();
        $this->save();

        return $nuevo;
    }
}

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
        'tiene_iva',
        'iva_tasa',
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
        'excedente',
        'notas',
    ];

    protected $casts = [
        'monto_mensual' => 'decimal:2',
        'tiene_iva' => 'boolean',
        'iva_tasa' => 'decimal:2',
        'tasa_moratoria' => 'decimal:2',
        'recargo_fijo' => 'decimal:2',
        'porcentaje_aumento' => 'decimal:2',
        'inflacion_periodo' => 'decimal:2',
        'excedente' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_vencimiento_renta' => 'date',
        'fecha_ultimo_aumento' => 'date',
    ];

    protected $appends = [
        'iva_monto',
        'monto_con_iva',
        'interes_moratorio',
        'total_adeudo',
        'total_facturado',
        'total_cobrado',
        'total_recargos',
        'saldo_cuenta',
        'periodos_vencidos',
        'porcentaje_aumento_total',
        'estado_cuenta',
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
     * IVA desglosado de la renta mensual (0 si la renta no causa IVA).
     */
    protected function ivaMonto(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->tiene_iva
                ? round(((float) $this->monto_mensual) * ((float) $this->iva_tasa / 100), 2)
                : 0.0,
        );
    }

    /**
     * Total mensual con IVA incluido (= importe antes de IVA + IVA).
     */
    protected function montoConIva(): Attribute
    {
        return Attribute::make(
            get: fn () => round(((float) $this->monto_mensual) + (float) $this->iva_monto, 2),
        );
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
     * Estado de cuenta simplificado del arrendatario:
     * "excedente" (pagó de más), "adeudo" (debe) o "pagado" (a mano, sin deber ni sobrar).
     */
    protected function estadoCuenta(): Attribute
    {
        return Attribute::make(get: function () {
            if (((float) $this->excedente) > 0) {
                return 'excedente';
            }
            if (((float) $this->saldo_cuenta) > 0) {
                return 'adeudo';
            }

            return 'pagado';
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
     * Mantiene "estado_pago" (usado en filtros, listas y el dashboard) alineado
     * con el adeudo real calculado a partir de las mensualidades y cobros. Sin
     * esto, el campo quedaba fijo en lo último capturado a mano y no reflejaba
     * pagos o cobros aplicados después, mostrando un estado incorrecto.
     */
    public function sincronizarEstadoPago(): void
    {
        $this->unsetRelation('pagos');
        $nuevo = ((float) $this->saldo_cuenta) > 0
            ? 'con_adeudo'
            : ($this->estado_pago === 'pagada' ? 'pagada' : 'al_corriente');

        if ($nuevo !== $this->estado_pago) {
            $this->estado_pago = $nuevo;
            $this->save();
        }
    }

    /**
     * Genera las mensualidades pendientes (cuentas por cobrar): una por cada mes
     * desde el inicio del contrato (o el alta de la renta) hasta el mes indicado
     * (por defecto, el mes actual). Ocurre automáticamente el día 1 de cada mes
     * (ver comando `rentas:generar-mensualidades` y el scheduler) y también se
     * puede disparar de forma manual (botón "Generar rentas del mes"). Es
     * idempotente: nunca duplica un periodo ya generado para esta renta.
     *
     * @param  string|null  $hastaPeriodo  Periodo límite "YYYY-MM" (incluido). Por defecto, el mes actual.
     * @return int Número de mensualidades creadas.
     */
    public function generarMensualidadesPendientes(?string $hastaPeriodo = null): int
    {
        $inicio = $this->fecha_inicio
            ? Carbon::parse($this->fecha_inicio)
            : ($this->created_at ? Carbon::parse($this->created_at) : Carbon::today());

        $cursor = $inicio->copy()->startOfMonth();
        $fin = $hastaPeriodo
            ? Carbon::createFromFormat('Y-m', $hastaPeriodo)->startOfMonth()
            : Carbon::today()->startOfMonth();

        $existentes = $this->pagos()->pluck('periodo')->all();
        $creadas = 0;

        while ($cursor->lessThanOrEqualTo($fin)) {
            $periodo = $cursor->format('Y-m');
            if (! in_array($periodo, $existentes, true)) {
                [$vencRenta, $vencPago] = $this->fechasDePeriodo($periodo);
                $this->pagos()->create([
                    'periodo' => $periodo,
                    'monto' => (float) $this->monto_mensual,
                    'fecha_vencimiento_renta' => $vencRenta->toDateString(),
                    'fecha_vencimiento_pago' => $vencPago->toDateString(),
                    'recargo' => 0,
                    'monto_pagado' => 0,
                    'fecha_pago' => null,
                    'estado' => 'pendiente',
                ]);
                $creadas++;
            }
            $cursor->addMonth();
        }

        $this->sincronizarEstadoPago();

        return $creadas;
    }

    /**
     * Aplica un movimiento bancario de tipo "cobro" contra el adeudo de esta renta:
     * abona a la mensualidad pendiente más antigua primero y va corriendo hacia las
     * siguientes. Lo que sobre después de cubrir todas las mensualidades generadas
     * se guarda como pago excedente (saldo a favor del arrendatario).
     *
     * Es aditivo (no reinicia lo ya registrado manualmente en cada mensualidad) y
     * guarda en el propio movimiento el detalle de lo aplicado para poder revertirlo
     * de forma exacta si el movimiento se edita o se elimina.
     */
    public function aplicarCobro(MovimientoBancario $movimiento): void
    {
        if ($movimiento->tipo !== 'cobro') {
            return;
        }

        $this->generarMensualidadesPendientes();

        $restante = round((float) $movimiento->monto, 2);
        $detalle = [];

        foreach ($this->pagos()->orderBy('periodo')->get() as $pago) {
            if ($restante <= 0) {
                break;
            }
            $saldoPeriodo = (float) $pago->saldo;
            if ($saldoPeriodo <= 0) {
                continue;
            }

            $aplicado = round(min($restante, $saldoPeriodo), 2);
            $pago->fijarMontoPagado((float) $pago->monto_pagado + $aplicado);
            $detalle[] = ['pago_renta_id' => $pago->id, 'monto' => $aplicado];
            $restante = round($restante - $aplicado, 2);
        }

        if ($restante > 0) {
            $this->excedente = round((float) $this->excedente + $restante, 2);
            $this->save();
        }

        $movimiento->aplicado_detalle = $detalle;
        $movimiento->excedente_aplicado = max(0, round($restante, 2));
        $movimiento->save();

        $this->sincronizarEstadoPago();
    }

    /**
     * Revierte exactamente lo que un movimiento de cobro había aplicado
     * (mensualidades y/o excedente), usando el detalle guardado al aplicarlo.
     */
    public function revertirCobro(MovimientoBancario $movimiento): void
    {
        foreach ((array) ($movimiento->aplicado_detalle ?? []) as $entry) {
            $pago = $this->pagos()->find($entry['pago_renta_id'] ?? null);
            if (! $pago) {
                continue;
            }
            $pago->fijarMontoPagado(max(0, (float) $pago->monto_pagado - (float) $entry['monto']));
        }

        if ((float) $movimiento->excedente_aplicado > 0) {
            $this->excedente = max(0, round((float) $this->excedente - (float) $movimiento->excedente_aplicado, 2));
            $this->save();
        }

        $movimiento->aplicado_detalle = [];
        $movimiento->excedente_aplicado = 0;
        $movimiento->save();

        $this->sincronizarEstadoPago();
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

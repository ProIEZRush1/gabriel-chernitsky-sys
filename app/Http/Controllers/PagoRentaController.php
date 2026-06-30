<?php

namespace App\Http\Controllers;

use App\Models\PagoRenta;
use App\Models\Renta;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PagoRentaController extends Controller
{
    /**
     * Registra una mensualidad (periodo) y, opcionalmente, su pago.
     */
    public function store(Request $request, Renta $renta)
    {
        $data = $request->validate([
            'periodo' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'monto' => ['nullable', 'numeric', 'min:0'],
            'monto_pagado' => ['nullable', 'numeric', 'min:0'],
            'recargo' => ['nullable', 'numeric', 'min:0'],
            'fecha_pago' => ['nullable', 'date'],
            'notas' => ['nullable', 'string'],
        ]);

        if ($renta->pagos()->where('periodo', $data['periodo'])->exists()) {
            return back()->with('error', "El periodo {$data['periodo']} ya está registrado.");
        }

        $renta->pagos()->create($this->buildAttributes($renta, $data));

        return back()->with('success', "Mensualidad {$data['periodo']} registrada.");
    }

    /**
     * Genera automáticamente las mensualidades desde la fecha de inicio hasta el mes actual.
     */
    public function generar(Renta $renta)
    {
        $inicio = $renta->fecha_inicio ? Carbon::parse($renta->fecha_inicio) : Carbon::today();
        $cursor = $inicio->copy()->startOfMonth();
        $fin = Carbon::today()->startOfMonth();

        $existentes = $renta->pagos()->pluck('periodo')->all();
        $creadas = 0;

        while ($cursor->lessThanOrEqualTo($fin)) {
            $periodo = $cursor->format('Y-m');
            if (! in_array($periodo, $existentes, true)) {
                $renta->pagos()->create($this->buildAttributes($renta, ['periodo' => $periodo]));
                $creadas++;
            }
            $cursor->addMonth();
        }

        return back()->with('success', $creadas > 0
            ? "Se generaron {$creadas} mensualidad(es)."
            : 'No hay mensualidades nuevas por generar.');
    }

    public function update(Request $request, PagoRenta $pago)
    {
        $data = $request->validate([
            'monto' => ['nullable', 'numeric', 'min:0'],
            'monto_pagado' => ['nullable', 'numeric', 'min:0'],
            'recargo' => ['nullable', 'numeric', 'min:0'],
            'fecha_pago' => ['nullable', 'date'],
            'notas' => ['nullable', 'string'],
        ]);

        $renta = $pago->renta;
        $monto = array_key_exists('monto', $data) && $data['monto'] !== null
            ? (float) $data['monto']
            : (float) $pago->monto;
        $recargo = (float) ($data['recargo'] ?? $pago->recargo);
        $pagado = (float) ($data['monto_pagado'] ?? $pago->monto_pagado);

        $pago->update([
            'monto' => $monto,
            'recargo' => $recargo,
            'monto_pagado' => $pagado,
            'fecha_pago' => $data['fecha_pago'] ?? $pago->fecha_pago,
            'notas' => $data['notas'] ?? $pago->notas,
            'estado' => $this->estadoPara($monto, $recargo, $pagado),
        ]);

        return back()->with('success', "Mensualidad {$pago->periodo} actualizada.");
    }

    public function destroy(PagoRenta $pago)
    {
        $periodo = $pago->periodo;
        $pago->delete();

        return back()->with('success', "Mensualidad {$periodo} eliminada.");
    }

    /**
     * Arma los atributos de una mensualidad calculando fechas y estado.
     */
    private function buildAttributes(Renta $renta, array $data): array
    {
        [$vencRenta, $vencPago] = $renta->fechasDePeriodo($data['periodo']);

        $monto = array_key_exists('monto', $data) && $data['monto'] !== null
            ? (float) $data['monto']
            : (float) $renta->monto_mensual;
        $recargo = (float) ($data['recargo'] ?? 0);
        $pagado = (float) ($data['monto_pagado'] ?? 0);

        return [
            'periodo' => $data['periodo'],
            'monto' => $monto,
            'fecha_vencimiento_renta' => $vencRenta->toDateString(),
            'fecha_vencimiento_pago' => $vencPago->toDateString(),
            'recargo' => $recargo,
            'monto_pagado' => $pagado,
            'fecha_pago' => $data['fecha_pago'] ?? null,
            'notas' => $data['notas'] ?? null,
            'estado' => $this->estadoPara($monto, $recargo, $pagado),
        ];
    }

    private function estadoPara(float $monto, float $recargo, float $pagado): string
    {
        if ($pagado >= $monto + $recargo && $pagado > 0) {
            return 'pagado';
        }

        return $pagado > 0 ? 'parcial' : 'pendiente';
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\MovimientoBancario;
use App\Models\Propiedad;
use App\Models\Renta;
use App\Models\Seguro;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $rentas = Renta::with('pagos')->get();
        // Genera las mensualidades del mes que falten y mantiene "estado_pago" al
        // día, igual que al entrar al listado de Rentas, para que los conteos y
        // totales de adeudo del dashboard nunca queden desactualizados.
        $rentas->each(fn (Renta $renta) => $renta->generarMensualidadesPendientes());

        $totalAdeudo = $rentas->sum(fn ($r) => (float) $r->saldo_cuenta);
        $interesMoratorio = $rentas->sum(fn ($r) => (float) $r->total_recargos);

        return Inertia::render('Dashboard', [
            'metrics' => [
                'propiedades' => Propiedad::count(),
                'seguros' => Seguro::count(),
                'rentas' => $rentas->count(),
                'movimientos' => MovimientoBancario::count(),
                'rentas_con_adeudo' => $rentas->filter(fn ($r) => (float) $r->saldo_cuenta > 0)->count(),
                'total_adeudo' => round($totalAdeudo, 2),
                'interes_moratorio' => round($interesMoratorio, 2),
                'renta_mensual' => round($rentas->sum(fn ($r) => (float) $r->monto_mensual), 2),
                'suma_asegurada' => round((float) Seguro::sum('suma_asegurada'), 2),
            ],
        ]);
    }
}

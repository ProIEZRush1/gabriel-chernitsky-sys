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
        $rentas = Renta::all();

        $totalAdeudo = $rentas->sum(fn ($r) => (float) $r->total_adeudo);
        $interesMoratorio = $rentas->sum(fn ($r) => (float) $r->interes_moratorio);

        return Inertia::render('Dashboard', [
            'metrics' => [
                'propiedades' => Propiedad::count(),
                'seguros' => Seguro::count(),
                'rentas' => $rentas->count(),
                'movimientos' => MovimientoBancario::count(),
                'rentas_con_adeudo' => $rentas->where('estado_pago', 'con_adeudo')->count(),
                'total_adeudo' => round($totalAdeudo, 2),
                'interes_moratorio' => round($interesMoratorio, 2),
                'renta_mensual' => round($rentas->sum(fn ($r) => (float) $r->monto_mensual), 2),
                'suma_asegurada' => round((float) Seguro::sum('suma_asegurada'), 2),
            ],
        ]);
    }
}

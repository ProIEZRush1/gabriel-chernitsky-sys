<?php

namespace App\Http\Controllers;

use App\Models\Propiedad;
use App\Models\Renta;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RentaController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');
        $estado = $request->input('estado_pago');

        $rentas = Renta::query()
            ->with('propiedad:id,nombre')
            ->when($q, fn ($query) => $query->where('inquilino', 'like', "%{$q}%"))
            ->when($estado, fn ($query) => $query->where('estado_pago', $estado))
            ->latest()
            ->get();

        // Genera las mensualidades pendientes de cada renta (cuenta por cobrar al día).
        // Los cobros del auxiliar bancario ya se aplican en tiempo real al registrarse.
        $rentas->each(fn (Renta $renta) => $renta->generarMensualidadesPendientes());
        $rentas->load('pagos');

        return Inertia::render('Rentas/Index', [
            'rentas' => $rentas,
            'filters' => ['q' => $q, 'estado_pago' => $estado],
            'resumen' => [
                'rentas' => $rentas->count(),
                'cobrado' => round($rentas->sum(fn ($r) => (float) $r->total_cobrado), 2),
                'por_cobrar' => round($rentas->sum(fn ($r) => (float) $r->saldo_cuenta), 2),
                'recargos' => round($rentas->sum(fn ($r) => (float) $r->total_recargos), 2),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Rentas/Create', [
            'propiedades' => Propiedad::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request)
    {
        Renta::create($this->validateData($request));

        return redirect()->route('rentas.index')
            ->with('success', 'Renta registrada correctamente.');
    }

    public function show(Renta $renta)
    {
        // Las mensualidades se generan automáticamente cada mes (o manualmente desde
        // el botón "Generar mensualidades"). Los cobros del auxiliar bancario ya se
        // aplican en tiempo real cuando se registran.
        $renta->generarMensualidadesPendientes();

        // Se cargan completas (totales y filtros de fecha/mes se resuelven en el navegador).
        $renta->load(['propiedad:id,nombre', 'pagos']);
        $movimientos = $renta->movimientos()->latest('fecha')->get();

        return Inertia::render('Rentas/Show', [
            'renta' => $renta,
            'movimientos' => $movimientos,
        ]);
    }

    public function edit(Renta $renta)
    {
        return Inertia::render('Rentas/Edit', [
            'renta' => $renta,
            'propiedades' => Propiedad::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(Request $request, Renta $renta)
    {
        $renta->update($this->validateData($request));

        return redirect()->route('rentas.index')
            ->with('success', 'Renta actualizada correctamente.');
    }

    public function destroy(Renta $renta)
    {
        $renta->delete();

        return redirect()->route('rentas.index')
            ->with('success', 'Renta eliminada.');
    }

    /**
     * Genera manualmente las mensualidades pendientes de TODAS las rentas
     * (lo mismo que ocurre automáticamente cada día 1 de mes).
     */
    public function generarTodas()
    {
        $creadas = 0;
        foreach (Renta::all() as $renta) {
            $creadas += $renta->generarMensualidadesPendientes();
        }

        return back()->with('success', $creadas > 0
            ? "Se generaron {$creadas} mensualidad(es) en total."
            : 'No hay mensualidades nuevas por generar; todo está al día.');
    }

    /**
     * Aplica un aumento de renta (manual por porcentaje o automático
     * según el porcentaje configurado + inflación del periodo).
     */
    public function aumentar(Request $request, Renta $renta)
    {
        $data = $request->validate([
            'porcentaje' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ]);

        $anterior = (float) $renta->monto_mensual;
        $nuevo = $renta->aplicarAumento($data['porcentaje'] ?? null);

        $pct = $anterior > 0 ? round((($nuevo - $anterior) / $anterior) * 100, 2) : 0;

        return back()->with(
            'success',
            "Renta actualizada: de $".number_format($anterior, 2)." a $".number_format($nuevo, 2)." (+{$pct}%)."
        );
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'propiedad_id' => ['nullable', 'exists:propiedades,id'],
            'inquilino' => ['required', 'string', 'max:255'],
            'monto_mensual' => ['required', 'numeric', 'min:0'],
            'tiene_iva' => ['nullable', 'boolean'],
            'iva_tasa' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'dia_pago' => ['required', 'integer', 'min:1', 'max:31'],
            'dias_gracia' => ['nullable', 'integer', 'min:0', 'max:60'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_vencimiento_renta' => ['nullable', 'date'],
            'estado_pago' => ['required', 'string', 'max:50'],
            'tasa_moratoria' => ['nullable', 'numeric', 'min:0'],
            'recargo_fijo' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_aumento' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'inflacion_periodo' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'fecha_ultimo_aumento' => ['nullable', 'date'],
            'meses_adeudo' => ['nullable', 'integer', 'min:0'],
            'notas' => ['nullable', 'string'],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\MovimientoBancario;
use App\Models\Renta;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MovimientoBancarioController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');
        $tipo = $request->input('tipo');
        $mes = $request->input('mes');
        $desde = $request->input('desde');
        $hasta = $request->input('hasta');

        $movimientos = MovimientoBancario::query()
            ->with('renta:id,inquilino')
            ->when($q, fn ($query) => $query->where(fn ($sub) => $sub
                ->where('concepto', 'like', "%{$q}%")
                ->orWhere('auxiliar', 'like', "%{$q}%")
                ->orWhere('referencia', 'like', "%{$q}%")))
            ->when($tipo, fn ($query) => $query->where('tipo', $tipo))
            ->when($mes, fn ($query) => $query->whereRaw("strftime('%Y-%m', fecha) = ?", [$mes]))
            ->when($desde, fn ($query) => $query->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($query) => $query->whereDate('fecha', '<=', $hasta))
            ->latest('fecha')
            ->get();

        return Inertia::render('Movimientos/Index', [
            'movimientos' => $movimientos,
            'filters' => ['q' => $q, 'tipo' => $tipo, 'mes' => $mes, 'desde' => $desde, 'hasta' => $hasta],
        ]);
    }

    public function create()
    {
        return Inertia::render('Movimientos/Create', [
            'rentas' => Renta::orderBy('inquilino')->get(['id', 'inquilino']),
        ]);
    }

    public function store(Request $request)
    {
        $movimiento = MovimientoBancario::create($this->validateData($request));

        if ($movimiento->tipo === 'cobro' && $movimiento->renta_id) {
            $movimiento->renta->aplicarCobro($movimiento);
        }

        return redirect()->route('movimientos.index')
            ->with('success', 'Movimiento bancario registrado correctamente.');
    }

    public function edit(MovimientoBancario $movimiento)
    {
        return Inertia::render('Movimientos/Edit', [
            'movimiento' => $movimiento,
            'rentas' => Renta::orderBy('inquilino')->get(['id', 'inquilino']),
        ]);
    }

    public function update(Request $request, MovimientoBancario $movimiento)
    {
        // Si el movimiento ya era un cobro aplicado a una renta, primero se revierte
        // exactamente lo que había aplicado antes de guardar los nuevos datos.
        if ($movimiento->tipo === 'cobro' && $movimiento->renta_id) {
            $movimiento->renta->revertirCobro($movimiento);
        }

        $movimiento->update($this->validateData($request));
        $movimiento->refresh();

        if ($movimiento->tipo === 'cobro' && $movimiento->renta_id) {
            $movimiento->renta->aplicarCobro($movimiento);
        }

        return redirect()->route('movimientos.index')
            ->with('success', 'Movimiento actualizado correctamente.');
    }

    public function destroy(MovimientoBancario $movimiento)
    {
        if ($movimiento->tipo === 'cobro' && $movimiento->renta_id) {
            $movimiento->renta->revertirCobro($movimiento);
        }

        $movimiento->delete();

        return redirect()->route('movimientos.index')
            ->with('success', 'Movimiento eliminado.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'auxiliar' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'max:50'],
            'concepto' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric'],
            'fecha' => ['nullable', 'date'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'renta_id' => ['nullable', 'exists:rentas,id'],
        ]);
    }
}

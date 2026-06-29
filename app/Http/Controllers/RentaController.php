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

        return Inertia::render('Rentas/Index', [
            'rentas' => $rentas,
            'filters' => ['q' => $q, 'estado_pago' => $estado],
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

    private function validateData(Request $request): array
    {
        return $request->validate([
            'propiedad_id' => ['nullable', 'exists:propiedades,id'],
            'inquilino' => ['required', 'string', 'max:255'],
            'monto_mensual' => ['required', 'numeric', 'min:0'],
            'dia_pago' => ['required', 'integer', 'min:1', 'max:31'],
            'fecha_inicio' => ['nullable', 'date'],
            'estado_pago' => ['required', 'string', 'max:50'],
            'tasa_moratoria' => ['nullable', 'numeric', 'min:0'],
            'meses_adeudo' => ['nullable', 'integer', 'min:0'],
            'notas' => ['nullable', 'string'],
        ]);
    }
}

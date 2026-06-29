<?php

namespace App\Http\Controllers;

use App\Models\Propiedad;
use App\Models\Seguro;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SeguroController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');
        $ramo = $request->input('ramo');

        $seguros = Seguro::query()
            ->with('propiedad:id,nombre')
            ->when($q, fn ($query) => $query->where(fn ($sub) => $sub
                ->where('asegurado', 'like', "%{$q}%")
                ->orWhere('aseguradora', 'like', "%{$q}%")
                ->orWhere('numero_poliza', 'like', "%{$q}%")
                ->orWhere('agente_venta', 'like', "%{$q}%")))
            ->when($ramo, fn ($query) => $query->where('ramo', $ramo))
            ->latest()
            ->get();

        return Inertia::render('Seguros/Index', [
            'seguros' => $seguros,
            'filters' => ['q' => $q, 'ramo' => $ramo],
        ]);
    }

    public function create()
    {
        return Inertia::render('Seguros/Create', [
            'propiedades' => Propiedad::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(Request $request)
    {
        Seguro::create($this->validateData($request));

        return redirect()->route('seguros.index')
            ->with('success', 'Póliza de seguro registrada correctamente.');
    }

    public function edit(Seguro $seguro)
    {
        return Inertia::render('Seguros/Edit', [
            'seguro' => $seguro,
            'propiedades' => Propiedad::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(Request $request, Seguro $seguro)
    {
        $seguro->update($this->validateData($request));

        return redirect()->route('seguros.index')
            ->with('success', 'Póliza actualizada correctamente.');
    }

    public function destroy(Seguro $seguro)
    {
        $seguro->delete();

        return redirect()->route('seguros.index')
            ->with('success', 'Póliza eliminada.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'ramo' => ['required', 'string', 'max:50'],
            'asegurado' => ['required', 'string', 'max:255'],
            'beneficiario' => ['nullable', 'string', 'max:255'],
            'aseguradora' => ['required', 'string', 'max:255'],
            'numero_poliza' => ['nullable', 'string', 'max:255'],
            'agente_venta' => ['nullable', 'string', 'max:255'],
            'suma_asegurada' => ['nullable', 'numeric', 'min:0'],
            'prima' => ['nullable', 'numeric', 'min:0'],
            'condiciones' => ['nullable', 'string'],
            'vigencia_inicio' => ['nullable', 'date'],
            'vigencia_fin' => ['nullable', 'date'],
            'estado' => ['required', 'string', 'max:50'],
            'propiedad_id' => ['nullable', 'exists:propiedades,id'],
        ]);
    }
}

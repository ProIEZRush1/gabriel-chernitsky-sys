<?php

namespace App\Http\Controllers;

use App\Models\Propiedad;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PropiedadController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q');

        $propiedades = Propiedad::query()
            ->when($q, fn ($query) => $query->where('nombre', 'like', "%{$q}%")
                ->orWhere('direccion', 'like', "%{$q}%")
                ->orWhere('ciudad', 'like', "%{$q}%"))
            ->withCount('rentas')
            ->latest()
            ->get();

        return Inertia::render('Propiedades/Index', [
            'propiedades' => $propiedades,
            'filters' => ['q' => $q],
        ]);
    }

    public function create()
    {
        return Inertia::render('Propiedades/Create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        Propiedad::create($data);

        return redirect()->route('propiedades.index')
            ->with('success', 'Propiedad registrada correctamente.');
    }

    public function edit(Propiedad $propiedade)
    {
        return Inertia::render('Propiedades/Edit', [
            'propiedad' => $propiedade,
        ]);
    }

    public function update(Request $request, Propiedad $propiedade)
    {
        $data = $this->validateData($request);

        $propiedade->update($data);

        return redirect()->route('propiedades.index')
            ->with('success', 'Propiedad actualizada correctamente.');
    }

    public function destroy(Propiedad $propiedade)
    {
        $propiedade->delete();

        return redirect()->route('propiedades.index')
            ->with('success', 'Propiedad eliminada.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'max:50'],
            'direccion' => ['required', 'string', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:255'],
            'valor_comercial' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['required', 'string', 'max:50'],
            'notas' => ['nullable', 'string'],
            'areas' => ['nullable', 'array'],
            'areas.*.nombre' => ['required', 'string', 'max:120'],
            'areas.*.renta' => ['nullable', 'numeric', 'min:0'],
            'areas.*.principal' => ['nullable', 'boolean'],
        ]);

        $data['areas'] = $this->normalizeAreas($request->input('areas', []));

        return $data;
    }

    /**
     * Deja una lista limpia de áreas rentables. La primera (o la marcada como
     * principal) es el inmueble principal y siempre permanece; las demás se
     * pueden agregar sin límite y quitar libremente.
     */
    private function normalizeAreas($areas): array
    {
        $areas = collect(is_array($areas) ? $areas : [])
            ->map(fn ($a) => [
                'nombre' => trim((string) ($a['nombre'] ?? '')),
                'renta' => ($a['renta'] ?? '') === '' || $a['renta'] === null ? null : (float) $a['renta'],
                'principal' => (bool) ($a['principal'] ?? false),
            ])
            ->filter(fn ($a) => $a['nombre'] !== '')
            ->values();

        if ($areas->isEmpty()) {
            return [['nombre' => 'Principal', 'renta' => null, 'principal' => true]];
        }

        // Garantiza que exactamente la primera quede marcada como principal.
        return $areas->map(fn ($a, $i) => [...$a, 'principal' => $i === 0])->all();
    }
}

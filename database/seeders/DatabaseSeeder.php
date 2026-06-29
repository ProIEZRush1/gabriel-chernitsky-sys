<?php

namespace Database\Seeders;

use App\Models\MovimientoBancario;
use App\Models\Propiedad;
use App\Models\Renta;
use App\Models\Seguro;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuario administrador idempotente.
        User::updateOrCreate(
            ['email' => 'gabriel-chernitsky@overcloud.us'],
            [
                'name' => 'Gabriel Chernitsky',
                'password' => Hash::make('0YGkzPIoQ0td'),
                'email_verified_at' => now(),
            ]
        );

        // Solo sembrar datos de ejemplo una vez (para no duplicar en re-ejecuciones).
        if (Propiedad::count() === 0) {
            $depto = Propiedad::create([
                'nombre' => 'Departamento Polanco 304',
                'tipo' => 'departamento',
                'direccion' => 'Av. Horacio 1234, Int. 304',
                'ciudad' => 'Ciudad de México',
                'valor_comercial' => 4850000,
                'estado' => 'rentada',
                'notas' => 'Edificio con amenidades, dos recámaras.',
            ]);

            $local = Propiedad::create([
                'nombre' => 'Local Comercial Roma Norte',
                'tipo' => 'local',
                'direccion' => 'Calle Orizaba 88, Local B',
                'ciudad' => 'Ciudad de México',
                'valor_comercial' => 7200000,
                'estado' => 'rentada',
                'notas' => 'Planta baja, alto flujo peatonal.',
            ]);

            $casa = Propiedad::create([
                'nombre' => 'Casa Jardines del Pedregal',
                'tipo' => 'casa',
                'direccion' => 'Calle Agua 215',
                'ciudad' => 'Ciudad de México',
                'valor_comercial' => 18500000,
                'estado' => 'disponible',
                'notas' => 'Cuatro recámaras, jardín amplio.',
            ]);

            Seguro::create([
                'ramo' => 'inmueble',
                'asegurado' => 'Gabriel Chernitsky',
                'beneficiario' => 'Familia Chernitsky',
                'aseguradora' => 'GNP Seguros',
                'numero_poliza' => 'INM-2025-0042',
                'agente_venta' => 'Laura Méndez',
                'suma_asegurada' => 4850000,
                'prima' => 18500,
                'condiciones' => 'Cobertura contra incendio, sismo y daños a terceros.',
                'vigencia_inicio' => now()->subMonths(3)->toDateString(),
                'vigencia_fin' => now()->addMonths(9)->toDateString(),
                'estado' => 'vigente',
                'propiedad_id' => $depto->id,
            ]);

            Seguro::create([
                'ramo' => 'auto',
                'asegurado' => 'Gabriel Chernitsky',
                'beneficiario' => 'Gabriel Chernitsky',
                'aseguradora' => 'Qualitas',
                'numero_poliza' => 'AUT-2025-1180',
                'agente_venta' => 'Carlos Ibáñez',
                'suma_asegurada' => 680000,
                'prima' => 14200,
                'condiciones' => 'Cobertura amplia, auto particular.',
                'vigencia_inicio' => now()->subMonth()->toDateString(),
                'vigencia_fin' => now()->addMonths(11)->toDateString(),
                'estado' => 'vigente',
                'propiedad_id' => null,
            ]);

            Seguro::create([
                'ramo' => 'medico',
                'asegurado' => 'Gabriel Chernitsky',
                'beneficiario' => 'Familia Chernitsky',
                'aseguradora' => 'AXA',
                'numero_poliza' => 'GMM-2025-7765',
                'agente_venta' => 'Laura Méndez',
                'suma_asegurada' => 5000000,
                'prima' => 42800,
                'condiciones' => 'Gastos médicos mayores, cobertura nacional.',
                'vigencia_inicio' => now()->subMonths(2)->toDateString(),
                'vigencia_fin' => now()->addMonths(10)->toDateString(),
                'estado' => 'vigente',
                'propiedad_id' => null,
            ]);

            $rentaDepto = Renta::create([
                'propiedad_id' => $depto->id,
                'inquilino' => 'María Fernanda López',
                'monto_mensual' => 28000,
                'dia_pago' => 5,
                'fecha_inicio' => now()->subYear()->toDateString(),
                'estado_pago' => 'al_corriente',
                'tasa_moratoria' => 5,
                'meses_adeudo' => 0,
                'notas' => 'Contrato a 12 meses con renovación automática.',
            ]);

            $rentaLocal = Renta::create([
                'propiedad_id' => $local->id,
                'inquilino' => 'Cafetería Aroma S.A. de C.V.',
                'monto_mensual' => 45000,
                'dia_pago' => 1,
                'fecha_inicio' => now()->subMonths(8)->toDateString(),
                'estado_pago' => 'con_adeudo',
                'tasa_moratoria' => 6,
                'meses_adeudo' => 2,
                'notas' => 'Adeudo de dos mensualidades, se aplica interés moratorio.',
            ]);

            MovimientoBancario::create([
                'auxiliar' => 'Cuenta Rentas BBVA',
                'tipo' => 'cobro',
                'concepto' => 'Cobro de renta mensual - Depto Polanco 304',
                'monto' => 28000,
                'fecha' => now()->subDays(10)->toDateString(),
                'referencia' => 'SPEI-883421',
                'renta_id' => $rentaDepto->id,
            ]);

            MovimientoBancario::create([
                'auxiliar' => 'Cuenta Rentas BBVA',
                'tipo' => 'transferencia',
                'concepto' => 'Pago de prima de seguro inmueble',
                'monto' => 18500,
                'fecha' => now()->subDays(20)->toDateString(),
                'referencia' => 'SPEI-771203',
                'renta_id' => null,
            ]);

            MovimientoBancario::create([
                'auxiliar' => 'Caja Local Roma',
                'tipo' => 'cobro',
                'concepto' => 'Abono parcial de renta - Local Roma Norte',
                'monto' => 20000,
                'fecha' => now()->subDays(3)->toDateString(),
                'referencia' => 'EFEC-0091',
                'renta_id' => $rentaLocal->id,
            ]);
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\MovimientoBancario;
use App\Models\Propiedad;
use App\Models\Renta;
use App\Models\Seguro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'name' => 'Gabriel Chernitsky',
            'email' => 'gabriel-chernitsky@overcloud.us',
        ]);
    }

    public function test_propiedad_se_guarda_y_persiste(): void
    {
        $this->actingAs($this->admin())
            ->post('/propiedades', [
                'nombre' => 'Casa Demo',
                'tipo' => 'casa',
                'direccion' => 'Calle Falsa 123',
                'ciudad' => 'CDMX',
                'valor_comercial' => 1000000,
                'estado' => 'disponible',
            ])->assertRedirect('/propiedades');

        $this->assertDatabaseHas('propiedades', ['nombre' => 'Casa Demo']);
        $this->assertSame('Casa Demo', Propiedad::first()->nombre);
    }

    public function test_seguro_se_guarda_y_persiste(): void
    {
        $this->actingAs($this->admin())
            ->post('/seguros', [
                'ramo' => 'auto',
                'asegurado' => 'Juan Pérez',
                'aseguradora' => 'Qualitas',
                'suma_asegurada' => 500000,
                'prima' => 9000,
                'estado' => 'vigente',
            ])->assertRedirect('/seguros');

        $this->assertDatabaseHas('seguros', ['asegurado' => 'Juan Pérez', 'ramo' => 'auto']);
        $this->assertSame('Juan Pérez', Seguro::first()->asegurado);
    }

    public function test_renta_calcula_interes_moratorio_y_persiste(): void
    {
        $this->actingAs($this->admin())
            ->post('/rentas', [
                'inquilino' => 'Ana López',
                'monto_mensual' => 10000,
                'dia_pago' => 5,
                'estado_pago' => 'con_adeudo',
                'tasa_moratoria' => 5,
                'meses_adeudo' => 2,
            ])->assertRedirect('/rentas');

        $renta = Renta::first();
        $this->assertSame('Ana López', $renta->inquilino);
        // 10000 * 5% * 2 = 1000
        $this->assertEquals(1000, $renta->interes_moratorio);
        // 10000 * 2 + 1000 = 21000
        $this->assertEquals(21000, $renta->total_adeudo);
    }

    public function test_movimiento_bancario_se_guarda_y_persiste(): void
    {
        $this->actingAs($this->admin())
            ->post('/movimientos', [
                'auxiliar' => 'Cuenta BBVA',
                'tipo' => 'cobro',
                'concepto' => 'Pago de renta',
                'monto' => 10000,
                'fecha' => '2026-01-15',
            ])->assertRedirect('/movimientos');

        $this->assertDatabaseHas('movimientos_bancarios', ['concepto' => 'Pago de renta']);
        $this->assertSame('Cuenta BBVA', MovimientoBancario::first()->auxiliar);
    }

    public function test_modulos_requieren_autenticacion(): void
    {
        $this->get('/propiedades')->assertRedirect('/login');
        $this->get('/seguros')->assertRedirect('/login');
        $this->get('/rentas')->assertRedirect('/login');
        $this->get('/movimientos')->assertRedirect('/login');
    }

    public function test_dashboard_muestra_conteos(): void
    {
        Propiedad::create(['nombre' => 'P1', 'tipo' => 'casa', 'direccion' => 'X', 'estado' => 'disponible']);

        $this->actingAs($this->admin())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('metrics.propiedades', 1));
    }
}

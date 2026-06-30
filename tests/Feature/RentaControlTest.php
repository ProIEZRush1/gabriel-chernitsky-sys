<?php

namespace Tests\Feature;

use App\Models\PagoRenta;
use App\Models\Renta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentaControlTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'name' => 'Gabriel Chernitsky',
            'email' => 'gabriel-chernitsky@overcloud.us',
        ]);
    }

    private function renta(array $overrides = []): Renta
    {
        return Renta::create(array_merge([
            'inquilino' => 'Ana López',
            'monto_mensual' => 10000,
            'dia_pago' => 5,
            'dias_gracia' => 3,
            'fecha_inicio' => now()->subMonths(2)->startOfMonth()->toDateString(),
            'estado_pago' => 'con_adeudo',
            'tasa_moratoria' => 5,
            'recargo_fijo' => 200,
        ], $overrides));
    }

    public function test_estado_de_cuenta_se_renderiza(): void
    {
        $renta = $this->renta();

        $this->actingAs($this->admin())
            ->get(route('rentas.show', $renta))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Rentas/Show')->where('renta.inquilino', 'Ana López'));
    }

    public function test_registrar_mensualidad_y_calcular_saldo(): void
    {
        $renta = $this->renta();

        $this->actingAs($this->admin())
            ->post(route('rentas.pagos.store', $renta), [
                'periodo' => now()->format('Y-m'),
                'monto' => 10000,
                'monto_pagado' => 4000,
            ])->assertRedirect();

        $pago = PagoRenta::first();
        $this->assertSame('parcial', $pago->estado);
        // Vencimiento de pago = día 5 + 3 de gracia, mes corriente.
        $this->assertNotNull($pago->fecha_vencimiento_pago);
        // Saldo del periodo = 10000 - 4000 = 6000 (sin recargo aún si no está vencido).
        $renta->refresh()->load('pagos');
        $this->assertEquals(6000, $renta->saldo_cuenta - $renta->total_recargos);
    }

    public function test_periodo_vencido_genera_recargo(): void
    {
        $renta = $this->renta(['dia_pago' => 1, 'dias_gracia' => 0]);
        $periodo = now()->subMonths(2)->format('Y-m');

        [$vr, $vp] = $renta->fechasDePeriodo($periodo);
        $pago = $renta->pagos()->create([
            'periodo' => $periodo,
            'monto' => 10000,
            'fecha_vencimiento_renta' => $vr->toDateString(),
            'fecha_vencimiento_pago' => $vp->toDateString(),
            'estado' => 'pendiente',
        ]);

        // 2 meses vencido: recargo fijo 200 + 10000 * 5% * 2 = 200 + 1000 = 1200.
        $this->assertGreaterThan(0, $pago->meses_vencido);
        $this->assertEquals('vencido', $pago->estado_calculado);
        $this->assertGreaterThanOrEqual(1200, (float) $pago->recargo_vigente);
    }

    public function test_generar_mensualidades_crea_periodos(): void
    {
        $renta = $this->renta(['fecha_inicio' => now()->subMonths(3)->startOfMonth()->toDateString()]);

        $this->actingAs($this->admin())
            ->post(route('rentas.pagos.generar', $renta))
            ->assertRedirect();

        // De hace 3 meses al mes actual = 4 periodos.
        $this->assertSame(4, $renta->pagos()->count());
    }

    public function test_aumento_manual_actualiza_renta(): void
    {
        $renta = $this->renta(['monto_mensual' => 10000]);

        $this->actingAs($this->admin())
            ->post(route('rentas.aumentar', $renta), ['porcentaje' => 10])
            ->assertRedirect();

        $this->assertEquals(11000, $renta->refresh()->monto_mensual);
        $this->assertNotNull($renta->fecha_ultimo_aumento);
    }

    public function test_aumento_automatico_usa_porcentaje_mas_inflacion(): void
    {
        $renta = $this->renta([
            'monto_mensual' => 10000,
            'porcentaje_aumento' => 4,
            'inflacion_periodo' => 6,
        ]);

        // Sin porcentaje explícito usa 4 + 6 = 10%.
        $this->actingAs($this->admin())
            ->post(route('rentas.aumentar', $renta))
            ->assertRedirect();

        $this->assertEquals(11000, $renta->refresh()->monto_mensual);
    }
}

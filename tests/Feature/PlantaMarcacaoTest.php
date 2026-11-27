<?php

namespace Tests\Feature;

use App\Models\PatchPanelSala;
use App\Models\Planta;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * DatabaseTransactions: tudo é desfeito ao final, o banco local não é alterado.
 */
class PlantaMarcacaoTest extends TestCase
{
    use DatabaseTransactions;

    private function cenario(): array
    {
        $admin = User::all()->first(fn ($u) => Gate::forUser($u)->allows('admin'));
        $planta = Planta::first();
        $ponto = PatchPanelSala::first();
        if (!$admin || !$planta || !$ponto) {
            $this->markTestSkipped('Precisa de admin, planta e ponto no banco local.');
        }
        return [$admin, $planta, $ponto];
    }

    public function test_salva_tamanho_geral_e_recorte(): void
    {
        [$admin, $planta] = $this->cenario();

        $this->actingAs($admin)
            ->putJson("/plantas/{$planta->id}/config", [
                'escala_marcadores' => 1.5,
                'recorte' => ['x' => 10, 'y' => 20, 'w' => 50, 'h' => 40],
            ])
            ->assertOk()
            ->assertJsonPath('recorte.w', 50);

        $planta->refresh();
        $this->assertEquals(1.5, $planta->escala_marcadores);
        $this->assertEquals(['x' => 10.0, 'y' => 20.0, 'w' => 50.0, 'h' => 40.0], $planta->recorteValido());

        // remover recorte
        $this->actingAs($admin)->putJson("/plantas/{$planta->id}/config", ['recorte' => null])->assertOk();
        $this->assertNull($planta->refresh()->recorteValido());
    }

    public function test_rejeita_recorte_invalido(): void
    {
        [$admin, $planta] = $this->cenario();

        $this->actingAs($admin)
            ->putJson("/plantas/{$planta->id}/config", ['recorte' => ['x' => 10, 'y' => 10, 'w' => 0, 'h' => 500]])
            ->assertStatus(422);
    }

    public function test_marca_ponto_com_rotulo_livre(): void
    {
        [$admin, $planta, $ponto] = $this->cenario();

        $this->actingAs($admin)
            ->putJson("/plantas/{$planta->id}/mark", [
                'patch_panel_sala_id' => $ponto->id,
                'planta_id' => $planta->id,
                'x' => 12.34, 'y' => 56.78,
                'label_position' => 'livre',
                'label_dx' => 3.5, 'label_dy' => -2,
            ])
            ->assertOk();

        $ponto->refresh();
        $this->assertSame('livre', $ponto->label_position);
        $this->assertEquals(3.5, (float) $ponto->label_dx);
        $this->assertEquals(-2, (float) $ponto->label_dy);
        $this->assertEquals(12.34, (float) $ponto->x);
    }

    public function test_visitante_nao_altera_marcacoes(): void
    {
        [, $planta, $ponto] = $this->cenario();

        $this->putJson("/plantas/{$planta->id}/mark", [
            'patch_panel_sala_id' => $ponto->id, 'planta_id' => $planta->id, 'x' => 1, 'y' => 1,
        ])->assertForbidden();

        $this->putJson("/plantas/{$planta->id}/config", ['escala_marcadores' => 2])->assertForbidden();
    }

    public function test_paginas_renderizam_com_recorte_e_rotulo_livre(): void
    {
        [$admin, $planta, $ponto] = $this->cenario();

        $planta->forceFill(['public' => true, 'escala_marcadores' => 1.3, 'recorte' => ['x' => 5, 'y' => 5, 'w' => 90, 'h' => 90]])->save();
        $ponto->update([
            'planta_id' => $planta->id, 'x' => 30, 'y' => 40, 'visible' => true,
            'label_position' => 'livre', 'label_dx' => 2, 'label_dy' => -1,
        ]);

        $this->actingAs($admin)->get("/plantas/{$planta->id}/mark")
            ->assertOk()
            ->assertSee('Pontos sem marcação')
            ->assertSee('Sem sala — escolha ao soltar')
            ->assertSee('data-livre="1"', false)
            ->assertSee('marker-livre', false)
            ->assertSee('--escala-marcadores: 1.3', false);

        $this->get("/plantas/public/{$planta->id}")
            ->assertOk()
            ->assertSee('aspect-ratio', false)
            ->assertSee('marker-livre', false);

        $this->get("/plantas/imprimir/{$planta->id}")
            ->assertOk()
            ->assertSee('"pos":"livre"', false);
    }
}

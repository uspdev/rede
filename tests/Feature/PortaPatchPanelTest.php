<?php

namespace Tests\Feature;

use App\Models\PatchPanel;
use App\Models\PatchPanelSala;
use App\Models\Sala;
use App\Models\TipoPorta;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Vínculo de porta do patch panel feito pelo diagrama do rack.
 * DatabaseTransactions: tudo é desfeito ao final, o banco local não é alterado.
 */
class PortaPatchPanelTest extends TestCase
{
    use DatabaseTransactions;

    private function cenario(): array
    {
        $admin = User::all()->first(fn ($u) => Gate::forUser($u)->allows('admin'));
        $pp = PatchPanel::first();
        $salas = Sala::take(2)->get();
        if (!$admin || !$pp || $salas->count() < 1) {
            $this->markTestSkipped('Precisa de admin, patch panel e sala no banco local.');
        }
        // porta livre para o teste
        $porta = collect(range(1, $pp->qtde_portas))
            ->first(fn ($p) => !PatchPanelSala::where('patch_panel_id', $pp->id)->where('porta', $p)->exists());
        if (!$porta) {
            $this->markTestSkipped('Patch panel sem porta livre.');
        }
        return [$admin, $pp, $salas, $porta];
    }

    public function test_vincula_e_edita_porta(): void
    {
        [$admin, $pp, $salas, $porta] = $this->cenario();
        $tipo = TipoPorta::first();

        $resp = $this->actingAs($admin)
            ->putJson("/patch-panels/{$pp->id}/porta/{$porta}", [
                'sala_id' => $salas[0]->id,
                'tipo_porta_id' => $tipo?->id,
                'comentario' => 'perto da janela',
                'tamanho' => 12.5,
            ]);
        $resp->assertOk();

        $v = PatchPanelSala::where('patch_panel_id', $pp->id)->where('porta', $porta)->firstOrFail();
        // o id do vínculo NOVO precisa voltar (a tela usa para marcar na planta em seguida)
        $this->assertSame($v->id, $resp->json('id'));
        $this->assertSame('perto da janela', $v->comentario);

        // editar mantém a marcação na planta
        $v->update(['x' => 10, 'y' => 20]);
        $this->actingAs($admin)
            ->putJson("/patch-panels/{$pp->id}/porta/{$porta}", [
                'sala_id' => $salas->last()->id,
                'comentario' => 'mudou',
            ])
            ->assertOk();

        $v->refresh();
        $this->assertSame('mudou', $v->comentario);
        $this->assertEquals($salas->last()->id, $v->sala_id);
        $this->assertEquals(10, (float) $v->x);
        $this->assertSame(1, PatchPanelSala::where('patch_panel_id', $pp->id)->where('porta', $porta)->count());
    }

    public function test_exige_sala_e_porta_valida(): void
    {
        [$admin, $pp, $salas] = $this->cenario();

        $this->actingAs($admin)->putJson("/patch-panels/{$pp->id}/porta/1", [])
            ->assertStatus(422)->assertJsonPath('message', 'Escolha a sala.');

        $this->actingAs($admin)->putJson("/patch-panels/{$pp->id}/porta/" . ($pp->qtde_portas + 1), ['sala_id' => $salas[0]->id])
            ->assertStatus(422);
    }

    public function test_desvincula_porta(): void
    {
        [$admin, $pp, $salas, $porta] = $this->cenario();

        PatchPanelSala::create(['patch_panel_id' => $pp->id, 'sala_id' => $salas[0]->id, 'porta' => $porta, 'user_id' => $admin->id]);

        $this->actingAs($admin)->deleteJson("/patch-panels/{$pp->id}/porta/{$porta}")
            ->assertOk()->assertJsonPath('removidos', 1);

        $this->assertDatabaseMissing('patch_panel_sala', ['patch_panel_id' => $pp->id, 'porta' => $porta]);
    }

    public function test_visitante_nao_vincula(): void
    {
        [, $pp, $salas, $porta] = $this->cenario();

        $this->putJson("/patch-panels/{$pp->id}/porta/{$porta}", ['sala_id' => $salas[0]->id])->assertForbidden();
        $this->deleteJson("/patch-panels/{$pp->id}/porta/{$porta}")->assertForbidden();
    }

    public function test_rack_mostra_formulario_da_porta(): void
    {
        [$admin, $pp] = $this->cenario();

        $this->actingAs($admin)->get("/racks/{$pp->rack_id}")
            ->assertOk()
            ->assertSee('id="pm-fundo"', false)
            ->assertSee('Buscar sala');
    }

    public function test_porta_sem_sala_vincula_e_marca_na_planta(): void
    {
        [$admin, $pp, $salas, $porta] = $this->cenario();
        $planta = \App\Models\Planta::first();
        if (!$planta) {
            $this->markTestSkipped('Precisa de uma planta.');
        }

        $id = $this->actingAs($admin)
            ->putJson("/patch-panels/{$pp->id}/porta/{$porta}", ['sala_id' => $salas[0]->id])
            ->assertOk()->json('id');
        $this->assertNotNull($id);

        $this->actingAs($admin)->postJson("/plantas/{$planta->id}/mark", [
            '_method' => 'PUT', 'planta_id' => $planta->id, 'patch_panel_sala_id' => $id,
            'x' => '40.5', 'y' => '30.25', 'fontsize' => 12, 'label_position' => 'left',
        ])->assertOk();

        $this->assertDatabaseHas('patch_panel_sala', ['id' => $id, 'planta_id' => $planta->id, 'label_position' => 'left']);
    }
}

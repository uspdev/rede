<?php

namespace Tests\Feature;

use App\Models\Conexao;
use App\Models\Equipamento;
use App\Models\PatchPanel;
use App\Models\PatchPanelSala;
use App\Models\Sala;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * DatabaseTransactions: tudo é desfeito ao final, o banco local não é alterado.
 */
class ExcluirPatchPanelTest extends TestCase
{
    use DatabaseTransactions;

    private function cenario(): array
    {
        $admin = User::all()->first(fn ($u) => Gate::forUser($u)->allows('admin'));
        $equip = Equipamento::first();
        $sala = Sala::first();
        if (!$admin || !$equip || !$sala) {
            $this->markTestSkipped('Precisa de admin, equipamento e sala no banco local.');
        }

        $pp = PatchPanel::create([
            'nome' => 'PP-TESTE-EXCLUSAO',
            'rack_id' => $equip->rack_id,
            'qtde_portas' => 24,
            'user_id' => $admin->id,
        ]);
        PatchPanelSala::create([
            'patch_panel_id' => $pp->id, 'sala_id' => $sala->id, 'porta' => 3,
            'user_id' => $admin->id, 'comentario' => 'vinculo-de-teste',
        ]);
        Conexao::where('equipamento_id', $equip->id)->where('porta_equipamento', 40)->delete();
        Conexao::create([
            'equipamento_id' => $equip->id, 'porta_equipamento' => 40,
            'patch_panel_id' => $pp->id, 'porta_patch_panel' => 7,
        ]);

        return [$admin, $pp, $sala, $equip];
    }

    public function test_tela_de_conferencia_lista_os_vinculos(): void
    {
        [$admin, $pp, $sala, $equip] = $this->cenario();

        $this->actingAs($admin)
            ->get("/patch-panels/{$pp->id}/excluir")
            ->assertOk()
            ->assertSee('vinculo-de-teste')
            ->assertSee($sala->nome)
            ->assertSee($equip->hostname)
            ->assertSee('1</strong> vínculo(s) com salas', false)
            ->assertSee('1</strong> conexão(ões) com switches', false);
    }

    public function test_exige_confirmacao(): void
    {
        [$admin, $pp] = $this->cenario();

        $this->actingAs($admin)
            ->delete("/patch-panels/{$pp->id}")
            ->assertSessionHasErrors('confirmo');

        $this->assertDatabaseHas('patch_panels', ['id' => $pp->id]);
    }

    public function test_exclui_patch_panel_com_todos_os_vinculos(): void
    {
        [$admin, $pp, , $equip] = $this->cenario();

        $this->actingAs($admin)
            ->delete("/patch-panels/{$pp->id}", ['confirmo' => '1'])
            ->assertRedirect(route('racks.show', ['rack' => $equip->rack_id]));

        $this->assertDatabaseMissing('patch_panels', ['id' => $pp->id]);
        $this->assertDatabaseMissing('patch_panel_sala', ['patch_panel_id' => $pp->id]);
        $this->assertDatabaseMissing('conexoes', ['patch_panel_id' => $pp->id]);
    }

    public function test_visitante_nao_exclui(): void
    {
        [, $pp] = $this->cenario();

        $this->delete("/patch-panels/{$pp->id}", ['confirmo' => '1'])->assertForbidden();
        $this->assertDatabaseHas('patch_panels', ['id' => $pp->id]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Conexao;
use App\Models\Equipamento;
use App\Models\PatchPanel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * DatabaseTransactions: tudo é desfeito ao final, o banco local não é alterado.
 */
class ExcluirEquipamentoTest extends TestCase
{
    use DatabaseTransactions;

    private function cenario(): array
    {
        $admin = User::all()->first(fn ($u) => Gate::forUser($u)->allows('admin'));
        $base = Equipamento::first();
        $pp = PatchPanel::first();
        if (!$admin || !$base || !$pp) {
            $this->markTestSkipped('Precisa de admin, equipamento e patch panel no banco local.');
        }

        $equip = Equipamento::create([
            'hostname' => 'SW-TESTE-EXCLUSAO',
            'ip' => '10.255.255.254',
            'rack_id' => $base->rack_id,
            'modelo_switch_id' => $base->modelo_switch_id,
            'user_id' => $admin->id,
        ]);
        Conexao::where('patch_panel_id', $pp->id)->where('porta_patch_panel', 11)->delete();
        Conexao::create([
            'equipamento_id' => $equip->id, 'porta_equipamento' => 5,
            'patch_panel_id' => $pp->id, 'porta_patch_panel' => 11,
        ]);

        return [$admin, $equip, $pp];
    }

    public function test_tela_de_conferencia_lista_conexoes(): void
    {
        [$admin, $equip, $pp] = $this->cenario();

        $this->actingAs($admin)
            ->get("/equipamentos/{$equip->id}/excluir")
            ->assertOk()
            ->assertSee('SW-TESTE-EXCLUSAO')
            ->assertSee($pp->nome)
            ->assertSee('1</strong> conexão(ões) com patch panels', false)
            ->assertSee('1</strong> leitura(s)', false);
    }

    public function test_exige_confirmacao(): void
    {
        [$admin, $equip] = $this->cenario();

        $this->actingAs($admin)
            ->delete("/equipamentos/{$equip->id}")
            ->assertSessionHasErrors('confirmo');

        $this->assertDatabaseHas('equipamentos', ['id' => $equip->id]);
    }

    public function test_exclui_equipamento_e_conexoes(): void
    {
        [$admin, $equip, $pp] = $this->cenario();

        $this->actingAs($admin)
            ->delete("/equipamentos/{$equip->id}", ['confirmo' => '1'])
            ->assertRedirect(route('racks.show', ['rack' => $equip->rack_id]));

        $this->assertDatabaseMissing('equipamentos', ['id' => $equip->id]);
        $this->assertDatabaseMissing('conexoes', ['equipamento_id' => $equip->id]);
        $this->assertDatabaseHas('patch_panels', ['id' => $pp->id]);
    }

    public function test_visitante_nao_exclui(): void
    {
        [, $equip] = $this->cenario();

        $this->delete("/equipamentos/{$equip->id}", ['confirmo' => '1'])->assertForbidden();
        $this->assertDatabaseHas('equipamentos', ['id' => $equip->id]);
    }
}

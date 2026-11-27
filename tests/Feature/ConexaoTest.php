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
 * Usa DatabaseTransactions (e não RefreshDatabase) para não apagar o banco local:
 * tudo que o teste grava é desfeito ao final.
 */
class ConexaoTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::all()->first(fn ($u) => Gate::forUser($u)->allows('admin'));
        if (!$admin) {
            $this->markTestSkipped('Nenhum usuário admin no banco local.');
        }
        return $admin;
    }

    private function dispositivos(): array
    {
        $equip = Equipamento::first();
        $pp = PatchPanel::first();
        if (!$equip || !$pp) {
            $this->markTestSkipped('Precisa de ao menos um equipamento e um patch panel no banco local.');
        }
        // garante portas livres para o teste
        Conexao::where('equipamento_id', $equip->id)->delete();
        Conexao::where('patch_panel_id', $pp->id)->delete();
        return [$equip, $pp];
    }

    public function test_conecta_e_desconecta_portas(): void
    {
        [$equip, $pp] = $this->dispositivos();
        $this->actingAs($this->admin());

        $resp = $this->postJson('/conexoes', [
            'equipamento_id' => $equip->id,
            'porta_equipamento' => 1,
            'patch_panel_id' => $pp->id,
            'porta_patch_panel' => 2,
        ]);

        $resp->assertCreated()
            ->assertJsonPath('conexao.sw', $equip->id)
            ->assertJsonPath('conexao.swPorta', 1)
            ->assertJsonPath('conexao.pp', $pp->id)
            ->assertJsonPath('conexao.ppPorta', 2)
            ->assertJsonPath('conexao.swLabel', $equip->hostname);

        $id = $resp->json('conexao.id');
        $this->assertDatabaseHas('conexoes', ['id' => $id]);

        $this->deleteJson("/conexoes/{$id}")->assertOk();
        $this->assertDatabaseMissing('conexoes', ['id' => $id]);
    }

    public function test_nao_permite_duas_conexoes_na_mesma_porta(): void
    {
        [$equip, $pp] = $this->dispositivos();
        $this->actingAs($this->admin());

        $this->postJson('/conexoes', [
            'equipamento_id' => $equip->id, 'porta_equipamento' => 1,
            'patch_panel_id' => $pp->id, 'porta_patch_panel' => 1,
        ])->assertCreated();

        // mesma porta do switch
        $this->postJson('/conexoes', [
            'equipamento_id' => $equip->id, 'porta_equipamento' => 1,
            'patch_panel_id' => $pp->id, 'porta_patch_panel' => 3,
        ])->assertStatus(422);

        // mesma porta do patch panel
        $this->postJson('/conexoes', [
            'equipamento_id' => $equip->id, 'porta_equipamento' => 2,
            'patch_panel_id' => $pp->id, 'porta_patch_panel' => 1,
        ])->assertStatus(422);
    }

    public function test_rejeita_porta_inexistente(): void
    {
        [$equip, $pp] = $this->dispositivos();
        $this->actingAs($this->admin());

        $this->postJson('/conexoes', [
            'equipamento_id' => $equip->id, 'porta_equipamento' => 1,
            'patch_panel_id' => $pp->id, 'porta_patch_panel' => $pp->qtde_portas + 1,
        ])->assertStatus(422);
    }

    public function test_visitante_nao_conecta(): void
    {
        [$equip, $pp] = $this->dispositivos();

        $this->postJson('/conexoes', [
            'equipamento_id' => $equip->id, 'porta_equipamento' => 1,
            'patch_panel_id' => $pp->id, 'porta_patch_panel' => 1,
        ])->assertForbidden();
    }

    public function test_pagina_do_rack_renderiza_com_conexao(): void
    {
        [$equip, $pp] = $this->dispositivos();
        $this->actingAs($this->admin());

        Conexao::create([
            'equipamento_id' => $equip->id, 'porta_equipamento' => 1,
            'patch_panel_id' => $pp->id, 'porta_patch_panel' => 1,
        ]);

        $this->get("/racks/{$equip->rack_id}")
            ->assertOk()
            ->assertSee('Conectar portas')
            ->assertSee('data-kind="sw"', false)
            ->assertSee('data-kind="pp"', false);

        $this->get("/patch-panels/{$pp->id}")
            ->assertOk()
            ->assertSee($equip->hostname . ' · porta 1');

        $this->get("/equipamentos/{$equip->id}")
            ->assertOk()
            ->assertSee('Conectada')
            ->assertSee($pp->nome);
    }
}

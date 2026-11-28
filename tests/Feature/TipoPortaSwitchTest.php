<?php

namespace Tests\Feature;

use App\Models\Equipamento;
use App\Models\ModeloSwitch;
use App\Models\Predio;
use App\Models\Rack;
use App\Models\PortaSwitch;
use App\Models\TipoPortaSwitch;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Usa DatabaseTransactions (e não RefreshDatabase) para não apagar o banco local.
 */
class TipoPortaSwitchTest extends TestCase
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

    public function test_crud_de_tipo_com_vlan_opcional(): void
    {
        $this->actingAs($this->admin());

        $this->get('/tipo-portas-switch')->assertOk()->assertSee('Tipos de Porta de Switch');
        $this->get('/tipo-portas-switch/create')->assertOk();
        $this->get('/tipo-portas/create')->assertOk()->assertSee('Patch Panel');

        $this->post('/tipo-portas-switch', ['nome' => 'Teste Sem VLAN', 'cor' => '#112233'])
            ->assertRedirect('/tipo-portas-switch');
        $this->assertNull(TipoPortaSwitch::where('nome', 'Teste Sem VLAN')->first()->vlan);

        $this->post('/tipo-portas-switch', ['nome' => 'Teste Com VLAN', 'cor' => '#112233', 'vlan' => 120])
            ->assertRedirect('/tipo-portas-switch');
        $tipo = TipoPortaSwitch::where('nome', 'Teste Com VLAN')->first();
        $this->assertSame(120, $tipo->vlan);

        $this->get("/tipo-portas-switch/{$tipo->id}/edit")->assertOk()->assertSee('120');
        $this->put("/tipo-portas-switch/{$tipo->id}", ['nome' => 'Teste Com VLAN', 'vlan' => ''])
            ->assertRedirect('/tipo-portas-switch');
        $this->assertNull($tipo->fresh()->vlan);

        $this->post('/tipo-portas-switch', ['nome' => 'VLAN Inválida', 'vlan' => 5000])
            ->assertSessionHasErrors('vlan');
    }

    public function test_define_e_remove_tipo_da_porta_do_switch(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $predio = Predio::create(['nome' => 'Prédio Teste TipoPortaSwitch', 'user_id' => $admin->id]);
        $tr = \App\Models\TelecomRoom::create(['predio_id' => $predio->id, 'nome' => 'TR 1']);
        $rack = Rack::create(['nome' => 'Rack Teste', 'telecom_room_id' => $tr->id, 'user_id' => $admin->id]);
        $modelo = ModeloSwitch::create(['nome' => 'Modelo Teste', 'fabricante' => 'X', 'qtde_portas' => 24, 'user_id' => $admin->id]);
        $equip = Equipamento::create(['hostname' => 'sw-teste', 'ip' => '10.0.0.250', 'rack_id' => $rack->id, 'modelo_switch_id' => $modelo->id, 'user_id' => $admin->id]);
        $tipo = TipoPortaSwitch::create(['nome' => 'Teste Uplink', 'cor' => '#ff0000', 'vlan' => 10]);

        $this->putJson("/equipamentos/{$equip->id}/porta/1/tipo", ['tipo_porta_switch_id' => $tipo->id])
            ->assertOk()->assertJsonPath('tipo.vlan', 10);
        $this->assertSame($tipo->id, PortaSwitch::where('equipamento_id', $equip->id)->where('porta', 1)->value('tipo_porta_switch_id'));

        // tipo em uso não pode ser removido
        $this->delete("/tipo-portas-switch/{$tipo->id}");
        $this->assertNotNull($tipo->fresh());

        $this->get("/equipamentos/{$equip->id}")->assertOk()->assertSee('Teste Uplink');
        $this->get("/racks/{$equip->rack_id}")->assertOk()->assertSee('Teste Uplink');

        // porta inexistente
        $this->putJson("/equipamentos/{$equip->id}/porta/9999/tipo", ['tipo_porta_switch_id' => $tipo->id])->assertStatus(422);

        $this->putJson("/equipamentos/{$equip->id}/porta/1/tipo", ['tipo_porta_switch_id' => null])->assertOk();
        $this->assertFalse(PortaSwitch::where('equipamento_id', $equip->id)->where('porta', 1)->exists());

        $this->delete("/tipo-portas-switch/{$tipo->id}");
        $this->assertNull($tipo->fresh());

        // excluir o equipamento leva junto os tipos das portas
        $tipo2 = TipoPortaSwitch::create(['nome' => 'Teste Acesso']);
        PortaSwitch::create(['equipamento_id' => $equip->id, 'porta' => 2, 'tipo_porta_switch_id' => $tipo2->id]);
        $this->delete("/equipamentos/{$equip->id}", ['confirmo' => '1'])->assertRedirect();
        $this->assertFalse(PortaSwitch::where('equipamento_id', $equip->id)->exists());
    }
}

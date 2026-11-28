<?php

namespace Tests\Feature;

use App\Models\Predio;
use App\Models\Rack;
use App\Models\TelecomRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Usa DatabaseTransactions (e não RefreshDatabase) para não apagar o banco local.
 */
class TelecomRoomTest extends TestCase
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

    public function test_cria_tr_e_rack_dentro_dela(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $predio = Predio::create(['nome' => 'Prédio Teste TR', 'user_id' => $admin->id]);

        $this->get("/telecom-rooms/create?predio_id={$predio->id}")->assertOk()->assertSee('TR 1');
        $this->post('/telecom-rooms', ['predio_id' => $predio->id, 'nome' => 'TR 1', 'descricao' => 'Térreo'])
            ->assertRedirect("/predios/{$predio->id}/trs");
        $tr = TelecomRoom::where('predio_id', $predio->id)->firstOrFail();

        // nome repetido no mesmo prédio
        $this->post('/telecom-rooms', ['predio_id' => $predio->id, 'nome' => 'TR 1'])->assertSessionHasErrors('nome');

        $this->get("/racks/create?telecom_room_id={$tr->id}")->assertOk()->assertSee('TR 1');
        $this->post('/racks', ['nome' => 'Rack X', 'telecom_room_id' => $tr->id])
            ->assertRedirect("/telecom-rooms/{$tr->id}");
        $rack = Rack::where('nome', 'Rack X')->where('telecom_room_id', $tr->id)->firstOrFail();
        $this->assertSame($predio->id, $rack->predio_id, 'predio_id do rack deve vir da TR');

        $this->get("/telecom-rooms/{$tr->id}")->assertOk()->assertSee('Rack X');
        $this->get("/predios/{$predio->id}")->assertOk()->assertSee('TR 1')->assertSee('Rack X');
        $this->get('/predios')->assertOk()->assertSee('Prédio Teste TR');
        $this->get("/racks/{$rack->id}")->assertOk()->assertSee('TR 1');
        $this->get("/racks/{$rack->id}/edit")->assertOk();

        // TR com rack não pode ser excluída
        $this->delete("/telecom-rooms/{$tr->id}");
        $this->assertNotNull($tr->fresh());

        // mover o rack para outra TR de outro prédio atualiza o prédio do rack
        $outro = Predio::create(['nome' => 'Prédio Teste TR 2', 'user_id' => $admin->id]);
        $tr2 = TelecomRoom::create(['predio_id' => $outro->id, 'nome' => 'TR 1']);
        $this->put("/racks/{$rack->id}", ['nome' => 'Rack X', 'telecom_room_id' => $tr2->id])->assertRedirect();
        $this->assertSame($outro->id, $rack->fresh()->predio_id);

        $this->delete("/telecom-rooms/{$tr->id}")->assertRedirect("/predios/{$predio->id}/trs");
        $this->assertNull($tr->fresh());
    }

    public function test_rack_exige_tr(): void
    {
        $this->actingAs($this->admin());
        $this->post('/racks', ['nome' => 'Sem TR'])->assertSessionHasErrors('telecom_room_id');
    }

    public function test_formularios_de_tipo_mostram_seletor_de_cor(): void
    {
        $this->actingAs($this->admin());
        $this->get('/tipo-portas/create')->assertOk()->assertSee('cor-picker', false);
        $this->get('/tipo-portas-switch/create')->assertOk()->assertSee('cor-picker', false);
    }
}

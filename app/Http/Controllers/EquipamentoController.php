<?php

namespace App\Http\Controllers;

use App\Models\Equipamento;
use App\Models\ModeloSwitch;
use App\Models\PatchPanel;
use App\Models\PatchPanelSala;
use App\Models\Conexao;
use App\Models\PortaSwitch;
use App\Models\TipoPortaSwitch;
use Illuminate\Support\Facades\DB;
use App\Models\Rack;
use App\Http\Requests\EquipamentoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EquipamentoController extends Controller
{
    public function create(Request $request)
    {
        Gate::authorize('admin');

        return view('equipamentos.create', [
            'racks' => Rack::with('telecomRoom', 'predio')->orderBy('predio_id')->orderBy('telecom_room_id')->orderBy('nome')->get(),
            'modelos' => ModeloSwitch::orderBy('fabricante')->orderBy('nome')->get(),
            'rack_selecionado' => $request->input('rack_id')
        ]);
    }

    public function store(EquipamentoRequest $request)
    {
        Gate::authorize('admin');

        $validated = $request->validated();

        $validated['ordem'] = Equipamento::where('rack_id', $request->rack_id)->max('ordem') + 1;

        $equipamento = Equipamento::create($validated);

        session()->flash('alert-success', 'Switch criado com sucesso!');
        return redirect()->route('racks.show', ['rack' => $equipamento->rack_id]);
    }

    public function show(Equipamento $equipamento)
    {
        Gate::authorize('admin');

        $conexoes = $equipamento->conexoes()->with('patchPanel.rack')->get();

        // Sala/tipo vinculados a cada porta de patch panel conectada
        $vinculos = PatchPanelSala::with('sala.predio', 'tipoPorta')
            ->whereIn('patch_panel_id', $conexoes->pluck('patch_panel_id')->unique())
            ->get()
            ->keyBy(fn ($v) => $v->patch_panel_id . ':' . $v->porta);

        return view('equipamentos.show', [
            'equipamento' => $equipamento,
            'conexoesPorPorta' => $conexoes->keyBy('porta_equipamento'),
            'vinculos' => $vinculos,
            'patchPanelsParaConectar' => $this->patchPanelsParaConectar($equipamento),
            'tiposPortaSwitch' => TipoPortaSwitch::orderBy('nome')->get(),
            'tiposPorPorta' => $equipamento->portasSwitch()->pluck('tipo_porta_switch_id', 'porta'),
        ]);
    }

    /**
     * Patch panels do mesmo prédio (os do mesmo rack primeiro), com portas ocupadas e salas/ambientes,
     * para o formulário "Conectar" da página do switch.
     */
    private function patchPanelsParaConectar(Equipamento $equipamento): array
    {
        $patchPanels = PatchPanel::with('rack.telecomRoom')
            ->whereHas('rack', fn ($q) => $q->where('predio_id', $equipamento->rack->predio_id))
            ->get();
        $ids = $patchPanels->pluck('id');

        $ocupadas = Conexao::with('equipamento')->whereIn('patch_panel_id', $ids)->get()
            ->groupBy('patch_panel_id');
        $salas = PatchPanelSala::with('sala')->whereIn('patch_panel_id', $ids)->get()
            ->groupBy('patch_panel_id');

        return $patchPanels
            ->sortBy(fn ($pp) => [$pp->rack_id === $equipamento->rack_id ? 0 : 1, $pp->rack->nome, $pp->ordem, $pp->nome])
            ->map(fn ($pp) => [
                'id' => $pp->id,
                'nome' => $pp->nome,
                'rack' => $pp->rack->nome . ($pp->rack->telecomRoom ? ' · ' . $pp->rack->telecomRoom->nome : ''),
                'mesmoRack' => $pp->rack_id === $equipamento->rack_id,
                'portas' => (int) $pp->qtde_portas,
                'ocupadas' => ($ocupadas[$pp->id] ?? collect())
                    ->mapWithKeys(fn ($c) => [$c->porta_patch_panel => ($c->equipamento?->hostname ?? 'switch') . ' porta ' . $c->porta_equipamento]),
                'salas' => ($salas[$pp->id] ?? collect())
                    ->mapWithKeys(fn ($v) => [$v->porta => $v->sala?->nome]),
            ])
            ->values()
            ->all();
    }

    /**
     * Define (ou remove, se vazio) o tipo de uma porta do switch.
     */
    public function salvarTipoPorta(Request $request, Equipamento $equipamento, int $porta)
    {
        Gate::authorize('admin');

        $total = (int) ($equipamento->modeloSwitch?->qtde_portas ?: 24);
        if ($porta < 1 || $porta > $total) {
            return response()->json(['message' => "O switch {$equipamento->hostname} tem apenas {$total} portas."], 422);
        }

        $dados = $request->validate([
            'tipo_porta_switch_id' => 'nullable|integer|exists:tipo_portas_switch,id',
        ]);

        if (empty($dados['tipo_porta_switch_id'])) {
            PortaSwitch::where('equipamento_id', $equipamento->id)->where('porta', $porta)->delete();
            return response()->json(['success' => true, 'tipo' => null]);
        }

        $portaSwitch = PortaSwitch::updateOrCreate(
            ['equipamento_id' => $equipamento->id, 'porta' => $porta],
            ['tipo_porta_switch_id' => $dados['tipo_porta_switch_id'], 'user_id' => auth()->id()]
        );

        return response()->json(['success' => true, 'tipo' => $portaSwitch->tipo()->first(['id', 'nome', 'cor', 'vlan'])]);
    }

    /**
     * Define (ou remove, se vazio) o mesmo tipo em várias portas do switch de uma vez.
     */
    public function salvarTipoPortas(Request $request, Equipamento $equipamento)
    {
        Gate::authorize('admin');

        $total = (int) ($equipamento->modeloSwitch?->qtde_portas ?: 24);
        $dados = $request->validate([
            'portas' => 'required|array|min:1',
            'portas.*' => "integer|distinct|between:1,{$total}",
            'tipo_porta_switch_id' => 'nullable|integer|exists:tipo_portas_switch,id',
        ], [
            'portas.required' => 'Selecione ao menos uma porta.',
            'portas.*.between' => "O switch {$equipamento->hostname} tem apenas {$total} portas.",
        ]);

        $portas = array_map('intval', $dados['portas']);
        $tipoId = $dados['tipo_porta_switch_id'] ?? null;

        DB::transaction(function () use ($equipamento, $portas, $tipoId) {
            if (!$tipoId) {
                PortaSwitch::where('equipamento_id', $equipamento->id)->whereIn('porta', $portas)->delete();
                return;
            }
            $agora = now();
            PortaSwitch::upsert(
                array_map(fn ($p) => [
                    'equipamento_id' => $equipamento->id,
                    'porta' => $p,
                    'tipo_porta_switch_id' => $tipoId,
                    'user_id' => auth()->id(),
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ], $portas),
                ['equipamento_id', 'porta'],
                ['tipo_porta_switch_id', 'user_id', 'updated_at']
            );
        });

        return response()->json(['success' => true, 'portas' => count($portas)]);
    }

    public function edit(Equipamento $equipamento)
    {
        Gate::authorize('admin');

        return view('equipamentos.edit', [
            'equipamento' => $equipamento,
            'racks' => Rack::with('telecomRoom', 'predio')->orderBy('predio_id')->orderBy('telecom_room_id')->orderBy('nome')->get(),
            'modelos' => ModeloSwitch::orderBy('fabricante')->orderBy('nome')->get(),
        ]);
    }

    public function update(EquipamentoRequest $request, Equipamento $equipamento)
    {
        Gate::authorize('admin');

        $equipamento->update($request->validated());

        // Tipos em portas que deixaram de existir (modelo com menos portas) são removidos
        $total = (int) ($equipamento->fresh('modeloSwitch')->modeloSwitch?->qtde_portas ?: 24);
        $removidos = $equipamento->portasSwitch()->where('porta', '>', $total)->delete();

        session()->flash('alert-success', 'Switch atualizado com sucesso!'
            . ($removidos ? " Tipo removido de {$removidos} porta(s) acima da {$total}." : ''));
        return redirect()->route('equipamentos.show', ['equipamento' => $equipamento]);
    }

    /**
     * Tela de conferência antes de excluir: mostra tudo que será apagado junto.
     */
    public function confirmarExclusao(Equipamento $equipamento)
    {
        Gate::authorize('admin');

        $equipamento->load('rack.predio', 'modeloSwitch');

        $conexoes = $equipamento->conexoes()
            ->with('patchPanel.rack')
            ->orderBy('porta_equipamento')
            ->get();

        $vinculos = PatchPanelSala::with('sala.predio', 'tipoPorta')
            ->whereIn('patch_panel_id', $conexoes->pluck('patch_panel_id')->unique())
            ->get()
            ->keyBy(fn ($v) => $v->patch_panel_id . ':' . $v->porta);


        return view('equipamentos.excluir', [
            'equipamento' => $equipamento,
            'conexoes' => $conexoes,
            'vinculos' => $vinculos,
        ]);
    }

    public function destroy(Request $request, Equipamento $equipamento)
    {
        Gate::authorize('admin');

        $request->validate([
            'confirmo' => 'accepted',
        ], [
            'confirmo.accepted' => 'Marque a confirmação para excluir o switch e suas conexões.',
        ]);

        $rack_id = $equipamento->rack_id;

        $conexoes = DB::transaction(function () use ($equipamento) {
            $equipamento->portasSwitch()->delete();
            $conexoes = $equipamento->conexoes()->delete();
            $equipamento->delete();

            return $conexoes;
        });

        session()->flash('alert-success', "Switch {$equipamento->hostname} excluído, com {$conexoes} conexão(ões) com patch panels.");
        return redirect()->route('racks.show', ['rack' => $rack_id]);
    }
}

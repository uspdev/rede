<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rack;
use App\Models\Predio;
use App\Models\TelecomRoom;
use App\Models\Conexao;
use App\Models\PatchPanelSala;
use App\Models\PatchPanel;
use App\Models\Equipamento;
use App\Http\Requests\RackRequest;
use Illuminate\Support\Facades\Gate;

class RackController extends Controller
{
    public function create(Request $request)
    {
        Gate::authorize('admin');
        $trSelecionada = $request->input('telecom_room_id');
        // compatibilidade com links antigos (?predio_id=): pré-seleciona a primeira TR do prédio
        if (!$trSelecionada && $request->filled('predio_id')) {
            $trSelecionada = TelecomRoom::where('predio_id', $request->input('predio_id'))->orderBy('nome')->value('id');
        }

        return view('racks.create', [
            'predios' => $this->prediosComTrs(),
            'tr_selecionada' => $trSelecionada,
        ]);
    }

    public function store(RackRequest $request)
    {
        Gate::authorize('admin');
        $rack = Rack::create($request->validated() + ['user_id' => auth()->id()]);
        session()->flash('alert-success', 'Rack criado com sucesso!');

        return redirect()->route('telecom-rooms.show', ['telecomRoom' => $rack->telecom_room_id]);
    }

    public function show(Rack $rack)
    {
        Gate::authorize('admin');

        return view('racks.show', [
            'rack' => $rack
        ]);
    }

    public function edit(Rack $rack)
    {
        Gate::authorize('admin');
        return view('racks.edit', [
            'rack' => $rack,
            'predios' => $this->prediosComTrs(),
        ]);
    }

    public function update(RackRequest $request, Rack $rack)
    {
        Gate::authorize('admin');
        $rack->update($request->validated() + ['user_id' => auth()->id()]);
        session()->flash('alert-success', 'Rack atualizado com sucesso!');

        return redirect()->route('racks.show', ['rack' => $rack]);
    }

    public function destroy(Rack $rack)
    {
        Gate::authorize('admin');

        if ($rack->patchPanels->isNotEmpty() || $rack->equipamentos->isNotEmpty()) {
            session()->flash('alert-danger', "Não foi possível excluir o rack {$rack->nome}: ele ainda tem patch panels ou switches. Exclua ou mova-os antes.");
            return back();
        }

        $voltar = $rack->telecom_room_id
            ? route('telecom-rooms.show', ['telecomRoom' => $rack->telecom_room_id])
            : route('predios.trs', ['predio' => $rack->predio_id]);
        $rack->delete();
        session()->flash('alert-success', "Rack {$rack->nome} excluído com sucesso.");

        return redirect($voltar);
    }

    /**
     * Folha de conferência do rack para imprimir / salvar em PDF e levar ao rack.
     */
    public function imprimir(Rack $rack)
    {
        Gate::authorize('admin');

        $rack->load('predio', 'telecomRoom');
        $patchPanels = $rack->patchPanels()->get();
        $switches = $rack->equipamentos()->with('modeloSwitch', 'portasSwitch.tipo')->get();

        // Conexões que envolvem este rack (dos dois lados)
        $conexoes = Conexao::with('equipamento.portasSwitch.tipo', 'patchPanel.rack')
            ->where(fn ($q) => $q->whereIn('equipamento_id', $switches->pluck('id'))
                ->orWhereIn('patch_panel_id', $patchPanels->pluck('id')))
            ->get();

        // Vínculos (sala/ambiente + tipo) das portas de patch panel envolvidas
        $vinculos = PatchPanelSala::with('sala', 'tipoPorta')
            ->whereIn('patch_panel_id', $patchPanels->pluck('id')->merge($conexoes->pluck('patch_panel_id'))->unique())
            ->get()
            ->keyBy(fn ($v) => $v->patch_panel_id . ':' . $v->porta);

        return view('racks.imprimir', [
            'rack' => $rack,
            'patchPanels' => $patchPanels,
            'switches' => $switches,
            'conexoesPP' => $conexoes->keyBy(fn ($c) => $c->patch_panel_id . ':' . $c->porta_patch_panel),
            'conexoesSW' => $conexoes->keyBy(fn ($c) => $c->equipamento_id . ':' . $c->porta_equipamento),
            'vinculos' => $vinculos,
        ]);
    }

    /** Prédios com suas TRs, para o select agrupado do formulário de rack */
    private function prediosComTrs()
    {
        return Predio::with(['telecomRooms' => fn ($q) => $q->orderBy('nome')])
            ->whereHas('telecomRooms')
            ->orderBy('nome')
            ->get();
    }

    public function reordenar(Rack $rack, Request $request)
    {
        Gate::authorize('admin');

        $request->validate([
            'itens' => 'required|array',
            'itens.*.tipo' => 'required|in:equipamento,patchpanel',
            'itens.*.id' => 'required|integer',
            'itens.*.ordem' => 'required|integer',
        ]);

        foreach ($request->itens as $item) {
            if ($item['tipo'] === 'equipamento') {
                Equipamento::where('id', $item['id'])->where('rack_id', $rack->id)
                    ->update(['ordem' => $item['ordem']]);
            } else {
                PatchPanel::where('id', $item['id'])->where('rack_id', $rack->id)
                    ->update(['ordem' => $item['ordem']]);
            }
        }

        return response()->json(['success' => true]);
    }
}

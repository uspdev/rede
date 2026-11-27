<?php

namespace App\Http\Controllers;

use App\Models\Equipamento;
use App\Models\ModeloSwitch;
use App\Models\PatchPanelSala;
use App\Models\Snapshot;
use App\Models\Mac;
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
            'racks' => Rack::all(),
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

        session()->flash('alert-success', 'Equipamento criado com sucesso!');
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
        ]);
    }

    public function edit(Equipamento $equipamento)
    {
        Gate::authorize('admin');

        return view('equipamentos.edit', [
            'equipamento' => $equipamento,
            'racks' => Rack::all(),
            'modelos' => ModeloSwitch::orderBy('fabricante')->orderBy('nome')->get(),
        ]);
    }

    public function update(EquipamentoRequest $request, Equipamento $equipamento)
    {
        Gate::authorize('admin');

        $equipamento->update($request->validated());

        session()->flash('alert-success', 'Equipamento atualizado com sucesso!');
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

        $portaIds = $equipamento->portas()->pluck('id');
        $snapshotIds = Snapshot::whereIn('porta_id', $portaIds)->pluck('id');

        return view('equipamentos.excluir', [
            'equipamento' => $equipamento,
            'conexoes' => $conexoes,
            'vinculos' => $vinculos,
            'historico' => [
                'portas' => $portaIds->count(),
                'snapshots' => $snapshotIds->count(),
                'macs' => Mac::whereIn('snapshot_id', $snapshotIds)->count(),
            ],
        ]);
    }

    public function destroy(Request $request, Equipamento $equipamento)
    {
        Gate::authorize('admin');

        $request->validate([
            'confirmo' => 'accepted',
        ], [
            'confirmo.accepted' => 'Marque a confirmação para excluir o equipamento e suas conexões.',
        ]);

        $rack_id = $equipamento->rack_id;

        $conexoes = DB::transaction(function () use ($equipamento) {
            // Histórico de portas (API/SNMP): as FKs não têm cascade, então apaga na ordem
            $portaIds = $equipamento->portas()->pluck('id');
            $snapshotIds = Snapshot::whereIn('porta_id', $portaIds)->pluck('id');
            Mac::whereIn('snapshot_id', $snapshotIds)->delete();
            Snapshot::whereIn('id', $snapshotIds)->delete();
            $equipamento->portas()->delete();

            $conexoes = $equipamento->conexoes()->delete();
            $equipamento->delete();

            return $conexoes;
        });

        session()->flash('alert-success', "Equipamento {$equipamento->hostname} excluído, com {$conexoes} conexão(ões) com patch panels.");
        return redirect()->route('racks.show', ['rack' => $rack_id]);
    }
}

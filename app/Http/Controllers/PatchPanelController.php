<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PatchPanel;
use Illuminate\Support\Facades\DB;
use App\Models\PatchPanelSala;
use App\Models\Rack;
use App\Models\Sala;
use App\Http\Requests\PatchPanelRequest;
use App\Http\Requests\VincularPortaPatchPanelRequest;
use Illuminate\Support\Facades\Gate;

class PatchPanelController extends Controller
{
    public function create(Request $request)
    {
        Gate::authorize('admin');
        $racks = Rack::all();
        $rack_id = $request->input('rack_id');

        return view('patch-panels.create', [
            'racks' => $racks,
            'rack_selecionado' => $rack_id
        ]);
    }

    public function store(PatchPanelRequest $request)
    {
        Gate::authorize('admin');
        $patchPanel = PatchPanel::create($request->validated() + ['user_id' => auth()->id()]);
        session()->flash('alert-success', 'Patch panel criado com sucesso!');
        return redirect()->route('racks.show', ['rack' => $patchPanel->rack_id]);
    }

    public function show(PatchPanel $patchPanel)
    {
        Gate::authorize('admin');
        
        $salasVinculadas = $patchPanel->salas()
            ->withPivot('porta', 'tipo_porta_id') 
            ->orderBy('salas.nome')
            ->orderBy('porta', 'asc')
            ->get();

        $salasPredio = Sala::where('predio_id', $patchPanel->rack->predio_id)->get();

        return view('patch-panels.show', [
            'patchPanel' => $patchPanel,
            'salasVinculadas' => $salasVinculadas, 
            'salasPredio' => $salasPredio,
        ]);
    }

    public function edit(PatchPanel $patchPanel)
    {
        Gate::authorize('admin');
        $racks = Rack::all();

        return view('patch-panels.edit', [
            'patchPanel' => $patchPanel,
            'racks' => $racks,
        ]);
    }

    public function update(PatchPanelRequest $request, PatchPanel $patchPanel)
    {
        Gate::authorize('admin');
        $patchPanel->update($request->validated() + ['user_id' => auth()->id()]);
        session()->flash('alert-success', 'Patch panel atualizado com sucesso!');
        return redirect()->route('patch-panels.show', ['patchPanel' => $patchPanel]);
    }

    public function selecionarSala(PatchPanel $patchPanel, Request $request)
    {
        Gate::authorize('admin');
        $porta = $request->query('porta');

        $salasPredio = Sala::where('predio_id', $patchPanel->rack->predio_id)->get();

        return view('patch-panels.selecionar-sala', [
            'patchPanel' => $patchPanel,
            'salasPredio' => $salasPredio,
            'porta' => $porta
        ]);
    }

    public function selecionarTipoPorta(PatchPanel $patchPanel, Sala $sala, Request $request)
    {
        Gate::authorize('admin');
        $porta = $request->query('porta');

        return view('patch-panels.selecionar-tipo-porta', [
            'patchPanel' => $patchPanel,
            'sala' => $sala,
            'porta' => $porta,
            'tipoPortas' => \App\Models\TipoPorta::all()
        ]);
    }

    public function vincularSala(VincularPortaPatchPanelRequest $request, PatchPanel $patchPanel)
    {
        Gate::authorize('admin');
        
        $porta = $request->porta ?? $request->query('porta');
        
        // Verificar se a porta já está vinculada
        if ($patchPanel->salas()->wherePivot('porta', $porta)->exists()) {
            session()->flash('alert-danger', 'Esta porta já está vinculada!');
            return redirect()->route('patch-panels.show', ['patchPanel' => $patchPanel]);
        }

        $dadosVinculo = [
            'porta' => $porta, 
            'user_id' => auth()->id(),
            'comentario' => $request->comentario,
            'tamanho' => $request->tamanho,
            'tipo_porta_id' => $request->tipo_porta_id // Pode ser null
        ];

        $patchPanel->salas()->attach($request->sala_id, $dadosVinculo);
        
        session()->flash('alert-success', 'Porta vinculada com sucesso!');
        return redirect()->route('patch-panels.show', ['patchPanel' => $patchPanel]);
    }

    public function desvincularSala(PatchPanel $patchPanel, Sala $sala, Request $request)
    {
        Gate::authorize('admin');
        $porta = $request->query('porta');

        $patchPanel->salas()->wherePivot('porta', $porta)->detach($sala->id);
        session()->flash('alert-success', 'Porta desvinculada com sucesso!');
        return redirect()->route('patch-panels.show', ['patchPanel' => $patchPanel]);
    }

    public function editarTipoPorta(PatchPanel $patchPanel, Sala $sala, Request $request)
    {
        Gate::authorize('admin');
        $porta = $request->query('porta');

        // Verificar se existe o vínculo
        $vinculo = $patchPanel->salas()
            ->wherePivot('porta', $porta)
            ->where('sala_id', $sala->id)
            ->first();

        if (!$vinculo) {
            session()->flash('alert-danger', 'Vínculo não encontrado!');
            return redirect()->route('patch-panels.show', ['patchPanel' => $patchPanel]);
        }

        return view('patch-panels.editar-tipo-porta', [
            'patchPanel' => $patchPanel,
            'sala' => $sala,
            'porta' => $porta,
            'vinculo' => $vinculo,
            'tipoPortaAtual' => $vinculo->pivot->tipo_porta_id,
            'tipoPortas' => \App\Models\TipoPorta::all()
        ]);
    }

    public function atualizarTipoPorta(Request $request, PatchPanel $patchPanel, Sala $sala)
    {
        Gate::authorize('admin');
        $porta = $request->porta;

        // Validar a requisição
        $request->validate([
            'tipo_porta_id' => 'nullable|exists:tipo_portas,id'
        ]);

        // Atualizar o tipo de porta no vínculo
        $patchPanel->salas()
            ->wherePivot('porta', $porta)
            ->where('sala_id', $sala->id)
            ->updateExistingPivot($sala->id, [
                'tipo_porta_id' => $request->tipo_porta_id,
                'comentario' => $request->comentario,
                'tamanho' => $request->tamanho,
                'updated_at' => now()
            ]);

        session()->flash('alert-success', 'Tipo de porta atualizado com sucesso!');
        return redirect()->route('patch-panels.show', ['patchPanel' => $patchPanel]);
    }

    /**
     * Cria ou atualiza o vínculo de uma porta com uma sala (usado pelo diagrama do rack).
     */
    public function salvarPorta(Request $request, PatchPanel $patchPanel, int $porta)
    {
        Gate::authorize('admin');

        if ($porta < 1 || $porta > $patchPanel->qtde_portas) {
            return response()->json(['message' => "O patch panel {$patchPanel->nome} tem apenas {$patchPanel->qtde_portas} portas."], 422);
        }

        $dados = $request->validate([
            'sala_id' => 'required|exists:salas,id',
            'tipo_porta_id' => 'nullable|exists:tipo_portas,id',
            'comentario' => 'nullable|string|max:255',
            'tamanho' => 'nullable|numeric|min:0',
        ], [
            'sala_id.required' => 'Escolha a sala.',
        ]);

        $vinculo = PatchPanelSala::where('patch_panel_id', $patchPanel->id)->where('porta', $porta)->first();

        $criado = !$vinculo;
        if ($vinculo) {
            // mantém a marcação na planta (x/y), que é o local físico da tomada
            $vinculo->update($dados + ['user_id' => auth()->id()]);
        } else {
            $vinculo = PatchPanelSala::create($dados + [
                'patch_panel_id' => $patchPanel->id,
                'porta' => $porta,
                'user_id' => auth()->id(),
            ]);
        }

        session()->flash('alert-success', "Porta {$porta} do patch panel {$patchPanel->nome} salva.");

        return response()->json(['success' => true, 'id' => $vinculo->id, 'criado' => $criado]);
    }

    /**
     * Remove o vínculo de uma porta com sala (e a marcação dela na planta, se houver).
     */
    public function removerPorta(PatchPanel $patchPanel, int $porta)
    {
        Gate::authorize('admin');

        $removidos = PatchPanelSala::where('patch_panel_id', $patchPanel->id)->where('porta', $porta)->delete();

        if ($removidos) {
            session()->flash('alert-success', "Porta {$porta} do patch panel {$patchPanel->nome} desvinculada.");
        }

        return response()->json(['success' => true, 'removidos' => $removidos]);
    }

    /**
     * Tela de conferência antes de excluir: mostra tudo que será apagado junto.
     */
    public function confirmarExclusao(PatchPanel $patchPanel)
    {
        Gate::authorize('admin');

        $patchPanel->load('rack.predio');

        $vinculosSalas = PatchPanelSala::with(['sala.predio', 'tipoPorta', 'planta'])
            ->where('patch_panel_id', $patchPanel->id)
            ->orderBy('porta')
            ->get();

        $conexoes = $patchPanel->conexoes()
            ->with('equipamento.rack')
            ->orderBy('porta_patch_panel')
            ->get();

        return view('patch-panels.excluir', [
            'patchPanel' => $patchPanel,
            'vinculosSalas' => $vinculosSalas,
            'conexoes' => $conexoes,
        ]);
    }

    public function destroy(Request $request, PatchPanel $patchPanel)
    {
        Gate::authorize('admin');

        $request->validate([
            'confirmo' => 'accepted',
        ], [
            'confirmo.accepted' => 'Marque a confirmação para excluir o patch panel e seus vínculos.',
        ]);

        $rack = $patchPanel->rack;

        $totais = DB::transaction(function () use ($patchPanel) {
            $conexoes = $patchPanel->conexoes()->delete();
            $salas = PatchPanelSala::where('patch_panel_id', $patchPanel->id)->delete();
            $patchPanel->delete();
            return compact('conexoes', 'salas');
        });

        session()->flash('alert-success', "Patch panel {$patchPanel->nome} excluído, com {$totais['salas']} vínculo(s) com salas e {$totais['conexoes']} conexão(ões) com switches.");

        return $rack
            ? redirect()->route('racks.show', ['rack' => $rack])
            : redirect('/');
    }
}

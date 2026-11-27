<?php

namespace App\Http\Controllers;

use App\Models\Conexao;
use App\Models\Equipamento;
use App\Models\PatchPanel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConexaoController extends Controller
{
    public function store(Request $request)
    {
        Gate::authorize('admin');

        $data = $request->validate([
            'equipamento_id' => 'required|integer|exists:equipamentos,id',
            'porta_equipamento' => 'required|integer|min:1',
            'patch_panel_id' => 'required|integer|exists:patch_panels,id',
            'porta_patch_panel' => 'required|integer|min:1',
        ]);

        $equipamento = Equipamento::with('modeloSwitch')->findOrFail($data['equipamento_id']);
        $patchPanel = PatchPanel::findOrFail($data['patch_panel_id']);

        $totalEquip = (int) ($equipamento->modeloSwitch?->qtde_portas ?: 24);
        if ($data['porta_equipamento'] > $totalEquip) {
            return response()->json(['message' => "O equipamento {$equipamento->hostname} tem apenas {$totalEquip} portas."], 422);
        }
        if ($data['porta_patch_panel'] > $patchPanel->qtde_portas) {
            return response()->json(['message' => "O patch panel {$patchPanel->nome} tem apenas {$patchPanel->qtde_portas} portas."], 422);
        }

        $ocupadaEquip = Conexao::where('equipamento_id', $equipamento->id)
            ->where('porta_equipamento', $data['porta_equipamento'])->exists();
        if ($ocupadaEquip) {
            return response()->json(['message' => "A porta {$data['porta_equipamento']} de {$equipamento->hostname} já está conectada."], 422);
        }

        $ocupadaPP = Conexao::where('patch_panel_id', $patchPanel->id)
            ->where('porta_patch_panel', $data['porta_patch_panel'])->exists();
        if ($ocupadaPP) {
            return response()->json(['message' => "A porta {$data['porta_patch_panel']} do patch panel {$patchPanel->nome} já está conectada."], 422);
        }

        $conexao = Conexao::create($data + ['user_id' => auth()->id()]);

        return response()->json(['success' => true, 'conexao' => $conexao->paraDiagrama()], 201);
    }

    public function destroy(Conexao $conexao)
    {
        Gate::authorize('admin');

        $conexao->delete();

        return response()->json(['success' => true]);
    }
}

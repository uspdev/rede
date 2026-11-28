<?php

namespace App\Support;

use App\Models\Conexao;
use App\Models\Equipamento;
use App\Models\ModeloSwitch;
use App\Models\PatchPanel;
use App\Models\PatchPanelSala;

/**
 * Descobre a maior porta em uso, para não deixar diminuir a quantidade de portas
 * abaixo dela (ex.: 24 portas com a 22 vinculada → pode ir para 22 ou 23, não para 12).
 *
 * Cada método devolve ['porta' => int, 'motivo' => string] ou null se nenhuma porta estiver em uso.
 */
class PortasEmUso
{
    /** Patch panel: vínculo com sala/ambiente ou patch cord para switch. */
    public static function patchPanel(PatchPanel $patchPanel): ?array
    {
        $vinculo = PatchPanelSala::with('sala')
            ->where('patch_panel_id', $patchPanel->id)
            ->orderByDesc('porta')
            ->first();
        $conexao = Conexao::with('equipamento')
            ->where('patch_panel_id', $patchPanel->id)
            ->orderByDesc('porta_patch_panel')
            ->first();

        $candidatos = array_filter([
            $vinculo ? ['porta' => (int) $vinculo->porta, 'motivo' => 'vinculada à sala/ambiente ' . ($vinculo->sala?->nome ?? '?')] : null,
            $conexao ? ['porta' => (int) $conexao->porta_patch_panel, 'motivo' => 'com patch cord para o switch ' . ($conexao->equipamento?->hostname ?? '?') . ' porta ' . $conexao->porta_equipamento] : null,
        ]);

        return $candidatos ? collect($candidatos)->sortByDesc('porta')->first() : null;
    }

    /** Switch: patch cord para patch panel. (Tipo de porta não bloqueia.) */
    public static function switch(Equipamento $equipamento): ?array
    {
        $conexao = Conexao::with('patchPanel')
            ->where('equipamento_id', $equipamento->id)
            ->orderByDesc('porta_equipamento')
            ->first();

        return $conexao ? [
            'porta' => (int) $conexao->porta_equipamento,
            'motivo' => 'com patch cord para o patch panel ' . ($conexao->patchPanel?->nome ?? '?') . ' porta ' . $conexao->porta_patch_panel,
        ] : null;
    }

    /** Modelo de switch: a maior porta em uso entre todos os switches desse modelo. */
    public static function modelo(ModeloSwitch $modelo): ?array
    {
        $conexao = Conexao::with('equipamento', 'patchPanel')
            ->whereIn('equipamento_id', $modelo->equipamentos()->pluck('id'))
            ->orderByDesc('porta_equipamento')
            ->first();

        return $conexao ? [
            'porta' => (int) $conexao->porta_equipamento,
            'motivo' => 'do switch ' . ($conexao->equipamento?->hostname ?? '?') . ' está com patch cord para o patch panel '
                . ($conexao->patchPanel?->nome ?? '?') . ' porta ' . $conexao->porta_patch_panel,
        ] : null;
    }
}

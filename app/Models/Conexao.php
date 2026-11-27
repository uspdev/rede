<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Patch cord ligando uma porta de equipamento a uma porta de patch panel.
 */
class Conexao extends Model
{
    protected $table = 'conexoes';

    protected $fillable = [
        'equipamento_id',
        'porta_equipamento',
        'patch_panel_id',
        'porta_patch_panel',
        'user_id',
    ];

    protected $casts = [
        'porta_equipamento' => 'integer',
        'porta_patch_panel' => 'integer',
    ];

    public function equipamento()
    {
        return $this->belongsTo(Equipamento::class);
    }

    public function patchPanel()
    {
        return $this->belongsTo(PatchPanel::class);
    }

    /**
     * Formato usado pelo JavaScript do diagrama do rack.
     */
    public function paraDiagrama(): array
    {
        $this->loadMissing('equipamento', 'patchPanel');

        return [
            'id' => $this->id,
            'sw' => $this->equipamento_id,
            'swPorta' => $this->porta_equipamento,
            'swLabel' => $this->equipamento?->hostname,
            'pp' => $this->patch_panel_id,
            'ppPorta' => $this->porta_patch_panel,
            'ppLabel' => $this->patchPanel?->nome,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

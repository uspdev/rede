<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PatchPanelSala extends Pivot
{
    protected $table = 'patch_panel_sala';

    // A tabela tem id autoincremento; sem isto, Pivot::create() não devolve o id gerado
    public $incrementing = true;

    protected $fillable = [
        'patch_panel_id',
        'sala_id',
        'porta',
        'user_id',
        'tipo_porta_id',
        'x',
        'y',
        'comentario',
        'tamanho',
        'planta_id',
        'fontsize',
        'label_position',
        'visible',
        'label_dx',
        'label_dy',
    ];

    protected $casts = [
        'visible' => 'boolean',
    ];

    public function tipoPorta()
    {
        return $this->belongsTo(TipoPorta::class, 'tipo_porta_id');
    }

    public function patchPanel()
    {
        return $this->belongsTo(PatchPanel::class);
    }

    public function sala()
    {
        return $this->belongsTo(Sala::class);
    }

    public function planta()
    {
        return $this->belongsTo(Planta::class);
    }
}
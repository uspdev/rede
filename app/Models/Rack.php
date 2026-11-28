<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rack extends Model
{
    protected $fillable = ['nome', 'predio_id', 'telecom_room_id', 'user_id'];

    protected static function booted(): void
    {
        // O prédio do rack é sempre o prédio da TR em que ele está
        static::saving(function (Rack $rack) {
            if ($rack->telecom_room_id && $rack->isDirty('telecom_room_id')) {
                $rack->predio_id = TelecomRoom::whereKey($rack->telecom_room_id)->value('predio_id');
            }
        });
    }

    public function telecomRoom()
    {
        return $this->belongsTo(TelecomRoom::class);
    }

    public function predio()
    {
        return $this->belongsTo(Predio::class);
    }

    public function patchPanels()
    {
        return $this->hasMany(PatchPanel::class)->orderBy('ordem');
    }

    public function equipamentos()
    {
        return $this->hasMany(Equipamento::class)->orderBy('ordem');
    }

    public function itensVisualizacao(): array
    {
        $itens = [];

        foreach ($this->equipamentos()->with('modeloSwitch')->orderBy('ordem')->get() as $eq) {
            $itens[] = [
                'id' => $eq->id,
                'tipo_item' => 'equipamento',
                'nome' => $eq->hostname,
                'subtitulo' => $eq->modeloSwitch?->nome ?? 'Sem modelo',
                'ordem' => $eq->ordem,
                'cor' => $eq->cor_tipo,
                'tipo' => $eq->tipo,
                'url' => "/equipamentos/{$eq->id}",
                'icone' => 'fa-network-wired',
            ];
        }

        foreach ($this->patchPanels()->orderBy('ordem')->get() as $pp) {
            $itens[] = [
                'id' => $pp->id,
                'tipo_item' => 'patchpanel',
                'nome' => $pp->nome,
                'subtitulo' => "{$pp->qtde_portas} portas",
                'ordem' => $pp->ordem,
                'cor' => 'warning',
                'tipo' => null,
                'url' => "/patch-panels/{$pp->id}",
                'icone' => 'fa-th',
            ];
        }

        usort($itens, fn($a, $b) => $a['ordem'] <=> $b['ordem']);

        return $itens;
    }
}
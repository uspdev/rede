<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipo atribuído a uma porta física de um switch.
 */
class PortaSwitch extends Model
{
    protected $table = 'portas_switch';

    protected $fillable = [
        'equipamento_id',
        'porta',
        'tipo_porta_switch_id',
        'user_id',
    ];

    protected $casts = [
        'porta' => 'integer',
    ];

    public function equipamento()
    {
        return $this->belongsTo(Equipamento::class);
    }

    public function tipo()
    {
        return $this->belongsTo(TipoPortaSwitch::class, 'tipo_porta_switch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

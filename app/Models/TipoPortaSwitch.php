<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipo de porta de switch (ex.: Acesso, Uplink, Câmeras), com VLAN opcional.
 */
class TipoPortaSwitch extends Model
{
    protected $table = 'tipo_portas_switch';

    protected $fillable = [
        'nome',
        'cor',
        'vlan',
        'user_id',
    ];

    protected $casts = [
        'vlan' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function portas()
    {
        return $this->hasMany(PortaSwitch::class);
    }
}

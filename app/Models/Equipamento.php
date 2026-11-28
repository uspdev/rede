<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipamento extends Model
{
    protected $fillable = [
        'hostname',
        'ip',
        'rack_id',
        'modelo_switch_id',
        'user_id',
        /* 'tipo', */
        'ordem',
        'comentario',
    ];

    public function conexoes()
    {
        return $this->hasMany(Conexao::class);
    }

    /** Tipos atribuídos às portas deste switch */
    public function portasSwitch()
    {
        return $this->hasMany(PortaSwitch::class);
    }

    public function rack()
    {
        return $this->belongsTo(Rack::class);
    }

    public function modeloSwitch()
    {
        return $this->belongsTo(ModeloSwitch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getQtdePortasAttribute()
    {
        return $this->modeloSwitch?->qtde_portas ?? 0;
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Porta;

class Equipamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'hostname',
        'model',
        'ip',
        'qtde_portas',
        'rack_id',
        'user_id',
        'poe_type' 
    ];

    protected $casts = [
        'poe_type' => 'boolean' 
    ];

    public function portas()
    {
        return $this->hasMany(Porta::class);
    }

    public function rack()
    {
        return $this->belongsTo(Rack::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
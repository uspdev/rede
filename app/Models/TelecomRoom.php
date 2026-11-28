<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Telecommunications Room (TR): sala técnica do prédio que abriga os racks.
 */
class TelecomRoom extends Model
{
    protected $table = 'telecom_rooms';

    protected $fillable = [
        'predio_id',
        'nome',
        'descricao',
        'user_id',
    ];

    public function predio()
    {
        return $this->belongsTo(Predio::class);
    }

    public function racks()
    {
        return $this->hasMany(Rack::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

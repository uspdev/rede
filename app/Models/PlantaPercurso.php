<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Percurso de infraestrutura desenhado sobre a planta (eletrocalha, canaleta, duto).
 * Os pontos ficam em % da imagem inteira da planta: [[x, y], ...].
 */
class PlantaPercurso extends Model
{
    protected $table = 'planta_percursos';

    /** Tipos e como cada um é desenhado */
    public const TIPOS = [
        'eletrocalha' => ['nome' => 'Eletrocalha', 'cor' => '#d97706', 'largura' => 3.5, 'traco' => null],
        'canaleta'    => ['nome' => 'Canaleta',    'cor' => '#2563eb', 'largura' => 2,   'traco' => null],
        'duto'        => ['nome' => 'Duto',        'cor' => '#7c3aed', 'largura' => 2,   'traco' => '6 4'],
    ];

    protected $fillable = ['planta_id', 'tipo', 'pontos', 'descricao', 'user_id'];

    protected $casts = ['pontos' => 'array'];

    public function planta()
    {
        return $this->belongsTo(Planta::class);
    }

    public function estilo(): array
    {
        return self::TIPOS[$this->tipo] ?? self::TIPOS['canaleta'];
    }

    /** "x1,y1 x2,y2 ..." para <polyline points> */
    public function pontosSvg(): string
    {
        return collect($this->pontos)->map(fn ($p) => round($p[0], 3) . ',' . round($p[1], 3))->implode(' ');
    }

    /**
     * Ponto (em %) na metade do comprimento do percurso. $aspecto = largura/altura da planta,
     * para medir o comprimento em proporção real (x e y em % têm escalas diferentes).
     */
    public function meio(float $aspecto = 1.0): ?array
    {
        $pts = $this->pontos ?? [];
        if (count($pts) < 2) {
            return null;
        }
        $seg = [];
        for ($i = 1; $i < count($pts); $i++) {
            $seg[] = hypot(($pts[$i][0] - $pts[$i - 1][0]) * $aspecto, $pts[$i][1] - $pts[$i - 1][1]);
        }
        $resto = array_sum($seg) / 2;
        foreach ($seg as $i => $d) {
            if ($resto <= $d || $i === count($seg) - 1) {
                $f = $d > 0 ? min(1, $resto / $d) : 0;
                return [
                    $pts[$i][0] + ($pts[$i + 1][0] - $pts[$i][0]) * $f,
                    $pts[$i][1] + ($pts[$i + 1][1] - $pts[$i][1]) * $f,
                ];
            }
            $resto -= $d;
        }
        return null;
    }

    public function paraJs(): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'pontos' => $this->pontos,
            'descricao' => $this->descricao,
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Planta extends Model
{
    protected $casts = [
        'recorte' => 'array',
        'escala_marcadores' => 'float',
        'public' => 'boolean',
    ];

    private ?array $viewBoxCache = null;

    public function predio(){
        return $this->belongsTo(Predio::class);
    }

    public function sala(){
        return $this->belongsTo(Sala::class);
    }

    public function markers()
    {
        return $this->hasMany(PatchPanelSala::class, 'planta_id');
    }

    public function salas()
    {
        return $this->hasMany(Sala::class);
    }

    /** Camada de infraestrutura: eletrocalhas, canaletas e dutos */
    public function percursos()
    {
        return $this->hasMany(PlantaPercurso::class);
    }

    /**
     * [minX, minY, largura, altura] do SVG. Lê só o cabeçalho do arquivo (pode ter vários MB).
     */
    public function viewBox(): array
    {
        if ($this->viewBoxCache !== null) {
            return $this->viewBoxCache;
        }

        $padrao = [0, 0, 1000, 1000];
        if (!$this->path || !Storage::exists($this->path)) {
            return $this->viewBoxCache = $padrao;
        }

        $stream = Storage::readStream($this->path);
        $cabecalho = $stream ? fread($stream, 16384) : '';
        if ($stream) {
            fclose($stream);
        }

        if (!preg_match('/<svg\b[^>]*>/is', $cabecalho, $tag)) {
            return $this->viewBoxCache = $padrao;
        }
        $tag = $tag[0];

        if (preg_match('/viewBox\s*=\s*["\']\s*([-\d.eE]+)[\s,]+([-\d.eE]+)[\s,]+([-\d.eE]+)[\s,]+([-\d.eE]+)/i', $tag, $m)) {
            return $this->viewBoxCache = [(float) $m[1], (float) $m[2], (float) $m[3], (float) $m[4]];
        }

        if (preg_match('/\bwidth\s*=\s*["\']([\d.]+)/i', $tag, $w) && preg_match('/\bheight\s*=\s*["\']([\d.]+)/i', $tag, $h)) {
            return $this->viewBoxCache = [0, 0, (float) $w[1], (float) $h[1]];
        }

        return $this->viewBoxCache = $padrao;
    }

    /**
     * Recorte válido (em % da imagem inteira) ou null.
     */
    public function recorteValido(): ?array
    {
        $r = $this->recorte;
        if (!is_array($r) || !isset($r['x'], $r['y'], $r['w'], $r['h'])) {
            return null;
        }
        $r = array_map('floatval', array_intersect_key($r, array_flip(['x', 'y', 'w', 'h'])));
        if ($r['w'] < 1 || $r['h'] < 1) {
            return null;
        }
        return $r;
    }

    /**
     * Estilos da "janela" do recorte. As coordenadas das marcações continuam em % da
     * imagem inteira: só deslocamos/ampliamos a camada que contém imagem + marcações.
     *  - moldura: elemento externo, com a proporção da área recortada
     *  - camada:  elemento interno (imagem + marcadores), do tamanho da planta inteira
     */
    public function estilosRecorte(): array
    {
        $r = $this->recorteValido();
        if (!$r) {
            return ['moldura' => 'position: relative;', 'camada' => 'position: relative;'];
        }

        [, , $vw, $vh] = $this->viewBox();
        $proporcao = ($r['w'] * $vw) / max(0.0001, $r['h'] * $vh);

        return [
            'moldura' => sprintf('position: relative; overflow: hidden; aspect-ratio: %.6F;', $proporcao),
            'camada' => sprintf(
                'position: absolute; width: %.6F%%; height: %.6F%%; left: %.6F%%; top: %.6F%%;',
                10000 / $r['w'],
                10000 / $r['h'],
                -$r['x'] / $r['w'] * 100,
                -$r['y'] / $r['h'] * 100
            ),
        ];
    }
}

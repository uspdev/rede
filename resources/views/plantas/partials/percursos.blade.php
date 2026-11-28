{{--
    Camada de infraestrutura (eletrocalhas, canaletas, dutos) sobre a planta.
    Deve ficar dentro do mesmo container da imagem (o que tem position: relative),
    antes dos marcadores, para as linhas ficarem por baixo deles.
    Espera $percursos (coleção de PlantaPercurso). Com $numerar = true, mostra o número
    de cada percurso (mesma ordem da tabela) no meio do caminho; precisa de $planta.
--}}
@if(($percursos ?? collect())->isNotEmpty())
    <svg class="planta-percursos" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"
         style="position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; overflow: visible;">
        @foreach($percursos as $percurso)
            @php $e = $percurso->estilo(); @endphp
            <g class="percurso" data-tipo="{{ $percurso->tipo }}">
                <polyline points="{{ $percurso->pontosSvg() }}" fill="none" stroke="rgba(0,0,0,.35)"
                          stroke-width="{{ $e['largura'] + 1 }}" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                <polyline points="{{ $percurso->pontosSvg() }}" fill="none" stroke="{{ $e['cor'] }}"
                          stroke-width="{{ $e['largura'] }}" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"
                          @if($e['traco']) stroke-dasharray="{{ $e['traco'] }}" @endif />
            </g>
        @endforeach
    </svg>
    @if($numerar ?? false)
        @php [, , $vbW, $vbH] = $planta->viewBox(); $aspecto = $vbH > 0 ? $vbW / $vbH : 1; @endphp
        @foreach($percursos->values() as $i => $percurso)
            @php $m = $percurso->meio($aspecto); $e = $percurso->estilo(); @endphp
            @if($m)
                <span class="percurso-num" data-tipo="{{ $percurso->tipo }}" title="{{ $e['nome'] }}{{ $percurso->descricao ? ' — ' . $percurso->descricao : '' }}"
                      style="left: {{ round($m[0], 3) }}%; top: {{ round($m[1], 3) }}%; background: {{ $e['cor'] }};">{{ $i + 1 }}</span>
            @endif
        @endforeach
        <style>
            .percurso-num {
                position: absolute; transform: translate(-50%, -50%) scale(var(--escala-marcadores, 1));
                min-width: 16px; height: 16px; padding: 0 3px; border-radius: 8px;
                display: inline-flex; align-items: center; justify-content: center;
                font: 700 10px/1 system-ui, sans-serif; color: #fff;
                border: 1.5px solid #fff; box-shadow: 0 0 0 1px rgba(0,0,0,.3); pointer-events: auto; cursor: help;
            }
        </style>
    @endif
@endif

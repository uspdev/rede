{{--
    Marcadores de pontos (patch panel ↔ sala) sobre a planta.

    Posição: left/top em % da planta inteira.
     - Modos right/left/top/bottom (legado): o x/y é o CENTRO do conjunto ícone + rótulo.
     - Modo livre: o x/y é o centro do ÍCONE; o rótulo fica deslocado (label_dx/label_dy, em "em")
       e ligado ao ícone por uma linha guia.
    Tamanho: fontsize × var(--escala-marcadores) (tamanho global da planta).
--}}
@once
<style>
    .marker-livre .marker-linha { position: absolute; left: 0; top: 0; width: 1px; height: 1px; overflow: visible; pointer-events: none; }
    .marker-livre .marker-icone { position: absolute; transform: translate(-50%, -50%); }
    .marker-livre .marker-label { position: absolute; transform: translate(-50%, -50%); }
    .marker-item.is-destaque .marker-icone { filter: drop-shadow(0 0 3px #facc15) drop-shadow(0 0 6px #f59e0b); animation: marker-pulso 0.9s ease-in-out infinite; }
    @keyframes marker-pulso { 50% { transform: scale(1.6); } }
    .marker-livre.is-destaque .marker-icone { animation-name: marker-pulso-livre; }
    @keyframes marker-pulso-livre { 0%, 100% { transform: translate(-50%, -50%); } 50% { transform: translate(-50%, -50%) scale(1.6); } }
</style>
@endonce

@foreach($markers as $marker)
    @php
        $size = $marker->fontsize ?? 12;
        $labelPosition = $marker->label_position ?? 'right';
        $nomeFormatado = optional(optional($marker->patchPanel)->rack)->nome . '-' . optional($marker->patchPanel)->nome . '-' . $marker->porta;

        $tooltipText = !empty($marker->comentario) ? $nomeFormatado . ' — ' . $marker->comentario : $nomeFormatado;

        // Recupera a cor do tipo de porta ou define vermelho (#ef4444) como fallback
        $corMarcador = optional($marker->tipoPorta)->cor ?? '#ef4444';

        // tamanho base em px, multiplicado pela escala global da planta
        $px = fn ($v) => 'calc(' . $v . 'px * var(--escala-marcadores, 1))';
        $sz = $px($size);

        // Distância segura entre o texto e o triângulo
        $spacing = $px(max(2, round($size * 0.3)));

        $livre = $labelPosition === 'livre';
        $dx = (float) ($marker->label_dx ?? 2.2);
        $dy = (float) ($marker->label_dy ?? -1.6);

        // Regras estritas de direção e espaçamento para EVITAR sobreposição
        switch($labelPosition) {
            case 'left':
                $flexStyles = 'flex-direction: row-reverse;';
                $textStyles = 'margin-right: ' . $spacing . ';';
                break;
            case 'top':
                $flexStyles = 'flex-direction: column-reverse;';
                $textStyles = 'writing-mode: vertical-rl; transform: rotate(180deg); margin-bottom: ' . $spacing . ';';
                break;
            case 'bottom':
                $flexStyles = 'flex-direction: column;';
                $textStyles = 'writing-mode: vertical-rl; transform: rotate(180deg); margin-top: ' . $spacing . ';';
                break;
            default: // right
                $flexStyles = 'flex-direction: row;';
                $textStyles = 'margin-left: ' . $spacing . ';';
                break;
        }
        $estiloTexto = 'color: #1e293b; font-size: ' . $sz . '; line-height: 1; margin: 0; padding: 0; white-space: nowrap; font-weight: 700; pointer-events: none; text-shadow: -1px -1px 0 #fff, 1px -1px 0 #fff, -1px 1px 0 #fff, 1px 1px 0 #fff;';
    @endphp

    <div class="marker-item marker-ponto ponto-marker {{ $livre ? 'marker-livre' : '' }}"
         data-id="{{ $marker->id }}"
         data-nome="{{ $nomeFormatado }}"
         data-tipo="{{ $marker->tipo_porta_id ?? '' }}"
         data-tipo-id="{{ $marker->tipo_porta_id ?? 0 }}"
         data-comentario="{{ $marker->comentario ?? '' }}"
         data-tamanho="{{ $marker->tamanho ?? '' }}"
         data-fontsize="{{ $size }}"
         data-label-position="{{ $labelPosition }}"
         data-labelposition="{{ $labelPosition }}"
         data-dx="{{ $dx }}"
         data-dy="{{ $dy }}"
         data-tooltip="{{ $tooltipText }}"
         @if($livre)
         style="position: absolute; left: {{ $marker->x }}%; top: {{ $marker->y }}%; width: 0; height: 0; cursor: pointer; z-index: 10; --fs: {{ $sz }};"
         @else
         style="position: absolute; left: {{ $marker->x }}%; top: {{ $marker->y }}%; transform: translate(-50%, -50%); cursor: pointer; z-index: 10; display: inline-flex; align-items: center; justify-content: center; {{ $flexStyles }}"
         @endif
         >

        @if($livre)
            <!-- Linha guia do ícone até o rótulo -->
            <svg class="marker-linha"><line x1="0" y1="0" x2="0" y2="0" stroke="#334155" stroke-width="1" stroke-dasharray="3 2" /></svg>
        @endif

        <!-- Ícone (Triângulo Dinâmico) -->
        <svg class="marker-icone" viewBox="0 0 100 100" style="width: {{ $sz }}; height: {{ $sz }}; display: block; pointer-events: none; flex-shrink: 0;">
            <polygon points="50,15 90,85 10,85" fill="{{ $corMarcador }}" stroke="#ffffff" stroke-width="8" />
        </svg>

        @if($livre)
            <span class="marker-label" style="left: calc(var(--fs) * {{ $dx }}); top: calc(var(--fs) * {{ $dy }}); {{ $estiloTexto }}">{{ $nomeFormatado }}</span>
        @else
            <!-- Texto com margem direcional proporcional ao tamanho da fonte -->
            <span class="marker-label" style="{{ $estiloTexto }} {{ $textStyles }}">
                {{ $nomeFormatado }}
            </span>
        @endif
    </div>
@endforeach

@once
<script>
    // Liga a linha guia ao rótulo nos marcadores "livres" (recalcula ao mudar tamanho/posição)
    window.atualizarLinhasGuia = function (raiz) {
        (raiz || document).querySelectorAll('.marker-livre').forEach(function (m) {
            var label = m.querySelector('.marker-label');
            var linha = m.querySelector('.marker-linha line');
            if (!label || !linha) return;
            linha.setAttribute('x2', label.offsetLeft);
            linha.setAttribute('y2', label.offsetTop);
        });
    };
    document.addEventListener('DOMContentLoaded', function () { window.atualizarLinhasGuia(); });
</script>
@endonce

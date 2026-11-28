@php
    // Cor usada quando a porta está vinculada mas não tem tipo definido
    $corPadraoVinculada = '#198754';

    $tiposPorta = \App\Models\TipoPorta::all()->keyBy('id');
    $patchPanelsDesenho = $rack->patchPanels()->with('salas.predio')->get();

    $legendaTipos = [];
    $temVinculoSemTipo = false;

    $paineis = $patchPanelsDesenho->map(function ($patchPanel) use ($tiposPorta, $corPadraoVinculada, &$legendaTipos, &$temVinculoSemTipo) {
        $portas = [];
        foreach ($patchPanel->salas as $sala) {
            $tipo = $tiposPorta->get($sala->pivot->tipo_porta_id);
            $cor = $tipo?->cor ?: $corPadraoVinculada;

            if ($tipo && $tipo->cor) {
                $legendaTipos[$tipo->id] = $tipo;
            } else {
                $temVinculoSemTipo = true;
            }

            $tip = $sala->nome;
            if ($sala->predio) {
                $tip .= ' (' . $sala->predio->nome . ')';
            }
            $tip .= ' · ' . ($tipo?->nome ?? 'Sem tipo');
            if ($sala->pivot->comentario) {
                $tip .= ' · ' . $sala->pivot->comentario;
            }

            $portas[$sala->pivot->porta] = [
                'cor' => $cor,
                'tip' => $tip,
                'sala_id' => $sala->id,
                'tipo_id' => $sala->pivot->tipo_porta_id,
                'tipo_nome' => $tipo?->nome,
                'comentario' => $sala->pivot->comentario,
                'tamanho' => $sala->pivot->tamanho,
                'marcada' => (bool) $sala->pivot->planta_id,
            ];
        }

        return ['model' => $patchPanel, 'portas' => $portas];
    });
@endphp

<style>
    .pp-wrap { overflow-x: auto; padding-bottom: 4px; }
    .pp-rack { min-width: 540px; display: flex; flex-direction: column; gap: 14px; }

    .pp-title { font-size: 13px; font-weight: 600; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: baseline; }
    .pp-title a { color: inherit; text-decoration: none; }
    .pp-title a:hover { text-decoration: underline; }
    .pp-title small { font-weight: 400; color: #6c757d; }

    .pp-panel {
        position: relative;
        display: flex;
        align-items: stretch;
        background: linear-gradient(180deg, #3a3f45 0%, #24282c 45%, #1b1e21 100%);
        border: 1px solid #111;
        border-radius: 3px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.12), 0 2px 4px rgba(0,0,0,.25);
    }
    .pp-ear {
        width: 18px;
        flex: 0 0 18px;
        background: linear-gradient(180deg, #5b6168, #3b4046);
        display: flex;
        flex-direction: column;
        justify-content: space-around;
        align-items: center;
    }
    .pp-ear:first-child { border-right: 1px solid #111; border-radius: 3px 0 0 3px; }
    .pp-ear:last-child  { border-left: 1px solid #111;  border-radius: 0 3px 3px 0; margin-left: auto; }
    .pp-ear span {
        width: 7px; height: 7px; border-radius: 50%;
        background: radial-gradient(circle at 35% 35%, #c9ced3, #6c737a);
        box-shadow: inset 0 0 1px #000;
    }

    .pp-body { padding: 4px 10px 6px; display: flex; flex-direction: column; gap: 6px; }
    .pp-row { display: flex; gap: 10px; }
    .pp-group { display: grid; grid-template-columns: repeat(6, 18px); gap: 3px; }

    .pp-port { display: flex; flex-direction: column; align-items: center; gap: 1px; }
    .pp-num { font: 600 8px/1 system-ui, sans-serif; color: #cfd4d9; display: flex; align-items: center; gap: 2px; }
    .pp-num i { width: 4px; height: 4px; border-radius: 50%; background: #3a3f44; display: inline-block; }
    .pp-jack {
        width: 100%;
        aspect-ratio: 1 / 0.9;
        border-radius: 2px;
        padding: 2px;
        background: #4a5057;
        box-shadow: inset 0 0 0 1px rgba(0,0,0,.5);
        transition: transform .1s, box-shadow .1s;
    }
    .pp-jack::before {
        /* abertura do conector RJ45 */
        content: "";
        display: block;
        width: 100%;
        height: 100%;
        background: #0b0c0d;
        clip-path: polygon(0 0, 100% 0, 100% 72%, 72% 72%, 72% 100%, 28% 100%, 28% 72%, 0 72%);
    }
    .pp-port.is-linked .pp-jack { background: var(--pp-cor); box-shadow: 0 0 4px var(--pp-cor), inset 0 0 0 1px rgba(0,0,0,.35); }

    /* Porta sem sala/ambiente: "?" para destacar que o destino não foi identificado */
    .pp-jack { position: relative; }
    .pp-port:not(.is-linked) .pp-jack::after,
    .pp-sem-sala {
        content: "?";
        font: 800 10px/1 system-ui, sans-serif;
        color: #ffc107;
        text-shadow: 0 0 2px #000, 0 0 3px #000;
    }
    .pp-port:not(.is-linked) .pp-jack::after {
        position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center;
        pointer-events: none;
    }
    .pp-sem-sala { display: inline-flex; align-items: center; justify-content: center; width: 14px; height: 14px; border-radius: 3px; background: #4a5057; border: 1px solid rgba(0,0,0,.35); }
    .pp-port:hover .pp-jack { transform: scale(1.15); }
</style>

@if($paineis->isEmpty())
    <div class="text-muted small">Nenhum patch panel para desenhar.</div>
@else
<div class="pp-wrap">
    <div class="pp-rack">
        @foreach($paineis as $painel)
            @php
                $patchPanel = $painel['model'];
                $totalPortas = (int) $patchPanel->qtde_portas;
                $linhas = array_chunk(range(1, max($totalPortas, 1)), 24);
            @endphp
            <div>
                <div class="pp-title">
                    <a href="{{ route('patch-panels.show', ['patchPanel' => $patchPanel]) }}">{{ $patchPanel->nome }}</a>
                    <small>{{ count($painel['portas']) }}/{{ $totalPortas }} vinculadas a salas/ambientes</small>
                </div>
                <div class="pp-panel">
                    <div class="pp-ear"><span></span>@if(count($linhas) > 1)<span></span>@endif</div>
                    <div class="pp-body">
                        @foreach($linhas as $linha)
                            <div class="pp-row">
                                @foreach(array_chunk($linha, 6) as $grupo)
                                    <div class="pp-group">
                                        @foreach($grupo as $porta)
                                            @php $info = $painel['portas'][$porta] ?? null; @endphp
                                            <div class="pp-port net-port {{ $info ? 'is-linked' : '' }}"
                                                 @if($info) style="--pp-cor: {{ $info['cor'] }};" @endif
                                                 data-kind="pp"
                                                 data-dev="{{ $patchPanel->id }}"
                                                 data-porta="{{ $porta }}"
                                                 data-label="{{ $patchPanel->nome }}"
                                                 @if($info)
                                                 data-sala-id="{{ $info['sala_id'] }}"
                                                 data-tipo-id="{{ $info['tipo_id'] }}"
                                                 data-tipo-nome="{{ $info['tipo_nome'] ?? '' }}"
                                                 data-comentario="{{ $info['comentario'] }}"
                                                 data-tamanho="{{ $info['tamanho'] }}"
                                                 data-marcada="{{ $info['marcada'] ? 1 : 0 }}"
                                                 @endif
                                                 data-tip="Patch panel {{ $patchPanel->nome }} · porta {{ $porta }} · {{ $info['tip'] ?? 'sem sala/ambiente' }}">
                                                <span class="pp-num"><i></i>{{ $porta }}</span>
                                                <span class="pp-jack"></span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                    <div class="pp-ear"><span></span>@if(count($linhas) > 1)<span></span>@endif</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="net-legend">
    @foreach($legendaTipos as $tipo)
        <span class="net-legend-item">
            <span class="net-swatch" style="background: {{ $tipo->cor }};"></span>{{ $tipo->nome }}
        </span>
    @endforeach
    @if($temVinculoSemTipo || empty($legendaTipos))
        <span class="net-legend-item">
            <span class="net-swatch" style="background: {{ $corPadraoVinculada }};"></span>Vinculada (sem tipo)
        </span>
    @endif
    <span class="net-legend-item">
        <span class="pp-sem-sala">?</span>Sem sala/ambiente
    </span>
    <span class="net-legend-item">
        <span class="net-legend-led" style="background: #2ecc71;"></span>Conectada a um switch
    </span>
</div>
@endif

@php
    $equipamentosDesenho = $rack->equipamentos()->with('modeloSwitch', 'portasSwitch.tipo')->get();

    /**
     * Calcula a posição (linha/coluna) de cada porta no painel frontal.
     * Blocos de até 48 portas, cada bloco com 2 linhas; colunas agrupadas de 6 em 6.
     *  - vertical:   1/2, 3/4, 5/6... (ímpares em cima)
     *  - horizontal: 1..metade em cima, o restante embaixo
     */
    $layoutPortas = function (int $total, string $numeracao) {
        $blocos = [];
        foreach (array_chunk(range(1, max($total, 1)), 48) as $bloco) {
            $cols = (int) ceil(count($bloco) / 2);
            $grupos = [];
            foreach ($bloco as $k => $porta) {
                if ($numeracao === 'vertical') {
                    $linha = $k % 2;
                    $col = intdiv($k, 2);
                } else {
                    $linha = intdiv($k, $cols);
                    $col = $k % $cols;
                }
                $grupos[intdiv($col, 6)][] = [
                    'porta' => $porta,
                    'linha' => $linha + 1,
                    'col' => $col % 6 + 1,
                ];
            }
            ksort($grupos);
            $blocos[] = $grupos;
        }
        return $blocos;
    };
@endphp

<style>
    .sw-wrap { overflow-x: auto; padding-bottom: 4px; }
    .sw-rack { min-width: 540px; display: flex; flex-direction: column; gap: 14px; }

    .sw-title { font-size: 13px; font-weight: 600; margin-bottom: 4px; display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
    .sw-title a { color: inherit; text-decoration: none; }
    .sw-title a:hover { text-decoration: underline; }
    .sw-title small { font-weight: 400; color: #6c757d; }

    .sw-chassis {
        display: flex;
        align-items: stretch;
        background: linear-gradient(180deg, #4b5560 0%, #2f363d 40%, #262b31 100%);
        border: 1px solid #14171a;
        border-radius: 3px;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.15), 0 2px 4px rgba(0,0,0,.25);
    }
    .sw-ear {
        width: 18px;
        flex: 0 0 18px;
        background: linear-gradient(180deg, #6a737c, #444b52);
        display: flex;
        flex-direction: column;
        justify-content: space-around;
        align-items: center;
    }
    .sw-ear:first-child { border-right: 1px solid #14171a; border-radius: 3px 0 0 3px; }
    .sw-ear:last-child  { border-left: 1px solid #14171a;  border-radius: 0 3px 3px 0; margin-left: auto; }
    .sw-ear span {
        width: 7px; height: 7px; border-radius: 50%;
        background: radial-gradient(circle at 35% 35%, #d5dade, #6c737a);
        box-shadow: inset 0 0 1px #000;
    }

    .sw-brand {
        flex: 0 0 52px;
        padding: 6px 6px;
        border-right: 1px solid rgba(0,0,0,.4);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        color: #dfe4e8;
    }
    .sw-model { font: 700 9px/1.1 system-ui, sans-serif; letter-spacing: .02em; word-break: break-word; }
    .sw-poe { font: 700 7px/1 system-ui, sans-serif; color: #1b1e21; background: #ffc107; border-radius: 2px; padding: 1px 3px; align-self: flex-start; margin-top: 3px; display: inline-block; }
    .sw-leds { display: flex; gap: 6px; margin-top: 6px; }
    .sw-led { display: flex; flex-direction: column; align-items: center; gap: 1px; font: 600 6px/1 system-ui, sans-serif; color: #aab2b9; }
    .sw-led i { width: 5px; height: 5px; border-radius: 50%; background: #2ecc71; box-shadow: 0 0 4px #2ecc71; display: block; }

    .sw-body { padding: 5px 8px; display: flex; flex-direction: column; justify-content: center; gap: 8px; }
    .sw-block { display: flex; gap: 8px; }
    .sw-group { display: grid; grid-template-columns: repeat(6, 17px); grid-template-rows: auto auto; gap: 2px 2px; align-content: start; }

    .sw-port { display: flex; flex-direction: column; align-items: center; gap: 1px; }
    .sw-port.bottom { flex-direction: column-reverse; }
    .sw-num { font: 600 7px/1 system-ui, sans-serif; color: #cfd4d9; display: flex; align-items: center; gap: 2px; }
    .sw-num i { width: 4px; height: 4px; border-radius: 50%; background: #3d444b; display: inline-block; }
    .sw-jack {
        width: 100%;
        aspect-ratio: 1 / 0.85;
        border-radius: 2px;
        padding: 2px;
        background: linear-gradient(180deg, #b8bec4, #8a9198);
        box-shadow: inset 0 0 0 1px rgba(0,0,0,.45);
        transition: transform .1s, box-shadow .1s;
    }
    .sw-jack::before {
        /* abertura RJ45: trava em cima nas portas de cima, embaixo nas de baixo */
        content: "";
        display: block;
        width: 100%;
        height: 100%;
        background: #0b0c0d;
        clip-path: polygon(0 0, 100% 0, 100% 72%, 72% 72%, 72% 100%, 28% 100%, 28% 72%, 0 72%);
    }
    .sw-port.top .sw-jack::before { transform: rotate(180deg); }
    .sw-port:hover .sw-jack { transform: scale(1.15); }
    /* faixa com a cor do tipo da porta do switch (fica entre as duas linhas de portas) */
    .sw-tipo { display: block; width: 100%; height: 3px; border-radius: 1px; }
</style>

@if($equipamentosDesenho->isEmpty())
    <div class="text-muted small">Nenhum switch para desenhar.</div>
@else
<div class="sw-wrap">
    <div class="sw-rack">
        @foreach($equipamentosDesenho as $equipamento)
            @php
                $modelo = $equipamento->modeloSwitch;
                $totalPortas = (int) ($modelo?->qtde_portas ?: 24);
                $blocos = $layoutPortas($totalPortas, $modelo?->numeracao_portas ?? 'horizontal');
                $tiposDasPortas = $equipamento->portasSwitch->keyBy('porta');
            @endphp
            <div class="sw-unit" data-dev="{{ $equipamento->id }}">
                <div class="sw-title">
                    <span>
                        @can('admin')
                            <a href="{{ route('equipamentos.show', ['equipamento' => $equipamento]) }}">{{ $equipamento->hostname }}</a>
                        @else
                            {{ $equipamento->hostname }}
                        @endcan
                    </span>
                    <small class="d-flex align-items-center gap-2">
                        {{ $equipamento->ip ?: 'sem IP' }} · {{ $totalPortas }} portas
                        <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 sw-ver-cabos" data-dev="{{ $equipamento->id }}"
                                title="Mostrar os patch cords deste switch até os patch panels">
                            <i class="fas fa-project-diagram"></i> <span class="rot-mostrar">Patch cords</span><span class="rot-ocultar">Ocultar patch cords</span>
                        </button>
                    </small>
                </div>
                <div class="sw-chassis">
                    <div class="sw-ear"><span></span>@if(count($blocos) > 1)<span></span>@endif</div>
                    <div class="sw-brand">
                        <div>
                            <div class="sw-model">{{ $modelo?->fabricante }} {{ $modelo?->nome ?? 'Sem modelo' }}</div>
                            @if($modelo?->poe)
                                <span class="sw-poe">PoE</span>
                            @endif
                        </div>
                        <div class="sw-leds">
                            <span class="sw-led"><i></i>PWR</span>
                            <span class="sw-led"><i></i>SYS</span>
                        </div>
                    </div>
                    <div class="sw-body">
                        @foreach($blocos as $grupos)
                            <div class="sw-block">
                                @foreach($grupos as $grupo)
                                    <div class="sw-group">
                                        @foreach($grupo as $p)
                                            @php $tipoSw = $tiposDasPortas->get($p['porta'])?->tipo; @endphp
                                            <div class="sw-port net-port {{ $p['linha'] === 1 ? 'top' : 'bottom' }}"
                                                 style="grid-row: {{ $p['linha'] }}; grid-column: {{ $p['col'] }};"
                                                 data-kind="sw"
                                                 data-dev="{{ $equipamento->id }}"
                                                 data-porta="{{ $p['porta'] }}"
                                                 data-label="{{ $equipamento->hostname }}"
                                                 data-tip="{{ $equipamento->hostname }} · porta {{ $p['porta'] }}"
                                                 data-tipo-id="{{ $tipoSw?->id }}"
                                                 data-tipo-cor="{{ $tipoSw?->cor }}"
                                                 data-tipo-nome="{{ $tipoSw ? $tipoSw->nome . ($tipoSw->vlan ? ' · VLAN ' . $tipoSw->vlan : '') : '' }}">
                                                <span class="sw-num"><i></i>{{ $p['porta'] }}</span>
                                                <span class="sw-jack"></span>
                                                <span class="sw-tipo" style="background: {{ $tipoSw?->cor ?: 'transparent' }};"></span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                    <div class="sw-ear"><span></span>@if(count($blocos) > 1)<span></span>@endif</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="net-legend">
    <span class="net-legend-item">
        <span class="net-legend-led" style="background: #2ecc71;"></span>Conectada a um patch panel
    </span>
    <span class="net-legend-item">
        <span class="sw-poe" style="margin: 0;">PoE</span>Modelo com PoE
    </span>
    @foreach($equipamentosDesenho->flatMap->portasSwitch->pluck('tipo')->filter()->unique('id')->sortBy('nome') as $t)
        <span class="net-legend-item">
            <span class="net-swatch" style="background: {{ $t->cor ?: 'transparent' }}; height: 6px;"></span>{{ $t->nome }}@if($t->vlan) (VLAN {{ $t->vlan }})@endif
        </span>
    @endforeach
</div>
@endif

@php
    $local = collect([$rack->telecomRoom?->nome, $rack->predio?->nome])->filter()->implode(' · ');
    $titulo = "Conferência do rack {$rack->nome}" . ($local ? " — {$local}" : '');

    $cor = fn ($c) => $c ?: 'transparent';
    $tipoSw = function ($switch, $porta) {
        return $switch?->portasSwitch->firstWhere('porta', $porta)?->tipo;
    };
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }}</title>
    <style id="estilo-pagina">@page { size: A4 portrait; margin: 10mm; }</style>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 9.5pt; color: #1e293b; background: #e2e8f0;
               -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        .controles { position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; gap: 14px; align-items: center;
                     padding: 10px 16px; background: #1e293b; color: #fff; font-size: 13px; }
        .controles button { background: #22c55e; color: #fff; border: 0; padding: 7px 14px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 13px; }
        .controles a { color: #cbd5e1; }
        .controles label { display: inline-flex; gap: 6px; align-items: center; cursor: pointer; }

        .folha { width: 210mm; min-height: 297mm; margin: 16px auto; padding: 10mm; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,.15); }

        header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; border-bottom: 2px solid #1e293b; padding-bottom: 6px; margin-bottom: 10px; }
        header h1 { font-size: 15pt; margin: 0 0 2px; }
        header .sub { color: #475569; }
        header .campos { text-align: right; line-height: 1.9; white-space: nowrap; }
        header .campos span { display: inline-block; min-width: 45mm; border-bottom: 1px solid #94a3b8; }

        .resumo { display: flex; flex-wrap: wrap; gap: 4px 14px; margin-bottom: 10px; color: #334155; }
        .legenda { display: flex; flex-wrap: wrap; gap: 4px 12px; margin: -4px 0 10px; font-size: 8.5pt; color: #475569; }
        .legenda .sw { vertical-align: -1px; }

        h2 { font-size: 11pt; margin: 14px 0 4px; padding: 3px 6px; background: #e2e8f0; border-left: 4px solid #1e293b; break-after: avoid; }
        h2 small { font-weight: normal; color: #475569; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; } /* repete o cabeçalho em cada página */
        th, td { border: 1px solid #94a3b8; padding: 2px 4px; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        th { background: #f1f5f9; font-size: 7.5pt; text-align: left; white-space: normal; line-height: 1.15; vertical-align: bottom; }
        th:first-child { text-align: center; }
        tr { break-inside: avoid; }
        td.num { text-align: center; font-weight: bold; }
        td.ok { text-align: center; }
        td.ok i { display: inline-block; width: 11px; height: 11px; border: 1.3px solid #1e293b; border-radius: 2px; }
        td.anot { background: repeating-linear-gradient(transparent 0 14px, #e2e8f0 14px 15px); }
        tr.livre td { color: #94a3b8; }
        .sw { display: inline-block; width: 9px; height: 9px; border-radius: 2px; border: 1px solid rgba(0,0,0,.35); margin-right: 3px; vertical-align: -1px; }
        .vazio { color: #94a3b8; }

        .obs { margin-top: 14px; break-inside: avoid; }
        .obs .linha { border-bottom: 1px solid #94a3b8; height: 7mm; }

        body.sem-livres tr.livre { display: none; }

        @media print {
            body { background: #fff; }
            .controles { display: none; }
            .folha { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="controles">
        <button type="button" onclick="window.print()">🖨 Imprimir / Salvar PDF</button>
        <label><input type="checkbox" id="opt-livres" checked> Incluir portas livres</label>
        <label><input type="checkbox" id="opt-paisagem"> Paisagem</label>
        <span style="opacity:.75;">Dica: na janela de impressão, escolha "Salvar como PDF" para gerar o arquivo.</span>
        <a href="{{ route('racks.show', ['rack' => $rack]) }}" style="margin-left:auto;">← Voltar ao rack</a>
    </div>

    <div class="folha">
        <header>
            <div>
                <h1>Rack {{ $rack->nome }}</h1>
                <div class="sub">
                    @if($rack->telecomRoom)Telecommunications Room: <strong>{{ $rack->telecomRoom->nome }}</strong> · @endif
                    Prédio: <strong>{{ $rack->predio?->nome }}</strong>
                </div>
                <div class="sub">Gerado em {{ now()->format('d/m/Y H:i') }}</div>
            </div>
            <div class="campos">
                Conferido por: <span></span><br>
                Data: <span></span>
            </div>
        </header>

        <div class="resumo">
            <span><strong>{{ $patchPanels->count() }}</strong> patch panel(s)</span>
            <span><strong>{{ $switches->count() }}</strong> switch(es)</span>
            <span><strong>{{ $conexoesSW->count() }}</strong> patch cord(s) cadastrados</span>
        </div>
        <div class="legenda">
            <span><span class="sw" style="background:#fff;"></span> Conferido: marque se o que está no rack bate com o cadastro</span>
            <span>Anotações: o que precisa ser corrigido (no rack ou no sistema)</span>
        </div>

        {{-- ================= PATCH PANELS (ficam acima dos switches no rack) ================= --}}
        @foreach($patchPanels as $pp)
            <h2>Patch panel {{ $pp->nome }} <small>· {{ $pp->qtde_portas }} portas</small></h2>
            <table>
                <colgroup>
                    <col style="width: 9mm"><col style="width: 30mm"><col style="width: 30mm">
                    <col style="width: 28mm"><col style="width: 36mm"><col style="width: 8mm"><col>
                </colgroup>
                <thead>
                    <tr>
                        <th>Nº</th><th>Sala/Ambiente</th><th>Tipo de porta (patch panel)</th>
                        <th>Switch · porta</th><th>Tipo da porta (switch)</th><th>OK</th><th>Anotações</th>
                    </tr>
                </thead>
                <tbody>
                    @for($n = 1; $n <= $pp->qtde_portas; $n++)
                        @php
                            $v = $vinculos->get($pp->id . ':' . $n);
                            $c = $conexoesPP->get($pp->id . ':' . $n);
                            $tSw = $c ? $tipoSw($c->equipamento, $c->porta_equipamento) : null;
                        @endphp
                        <tr class="{{ !$v && !$c ? 'livre' : '' }}">
                            <td class="num">{{ $n }}</td>
                            <td>{!! $v?->sala ? e($v->sala->nome) : '<span class="vazio">—</span>' !!}</td>
                            <td>
                                @if($v?->tipoPorta)<span class="sw" style="background: {{ $cor($v->tipoPorta->cor) }};"></span>{{ $v->tipoPorta->nome }}@else<span class="vazio">—</span>@endif
                            </td>
                            <td>
                                @if($c){{ $c->equipamento?->hostname }} · <strong>{{ $c->porta_equipamento }}</strong>@else<span class="vazio">não ligada</span>@endif
                            </td>
                            <td>
                                @if($tSw)<span class="sw" style="background: {{ $cor($tSw->cor) }};"></span>{{ $tSw->nome }}@if($tSw->vlan) <small>(VLAN {{ $tSw->vlan }})</small>@endif @else<span class="vazio">—</span>@endif
                            </td>
                            <td class="ok"><i></i></td>
                            <td class="anot"></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        @endforeach

        {{-- ================= SWITCHES ================= --}}
        @foreach($switches as $sw)
            @php $total = (int) ($sw->modeloSwitch?->qtde_portas ?: 24); @endphp
            <h2>
                Switch {{ $sw->hostname }}
                <small>· {{ $sw->ip ?: 'sem IP' }} · {{ trim(($sw->modeloSwitch?->fabricante ?? '') . ' ' . ($sw->modeloSwitch?->nome ?? '')) ?: 'sem modelo' }} · {{ $total }} portas</small>
            </h2>
            <table>
                <colgroup>
                    <col style="width: 9mm"><col style="width: 36mm"><col style="width: 28mm">
                    <col style="width: 30mm"><col style="width: 30mm"><col style="width: 8mm"><col>
                </colgroup>
                <thead>
                    <tr>
                        <th>Nº</th><th>Tipo da porta (switch)</th><th>Patch panel · porta</th>
                        <th>Sala/Ambiente</th><th>Tipo de porta (patch panel)</th><th>OK</th><th>Anotações</th>
                    </tr>
                </thead>
                <tbody>
                    @for($n = 1; $n <= $total; $n++)
                        @php
                            $t = $tipoSw($sw, $n);
                            $c = $conexoesSW->get($sw->id . ':' . $n);
                            $v = $c ? $vinculos->get($c->patch_panel_id . ':' . $c->porta_patch_panel) : null;
                        @endphp
                        <tr class="{{ !$t && !$c ? 'livre' : '' }}">
                            <td class="num">{{ $n }}</td>
                            <td>
                                @if($t)<span class="sw" style="background: {{ $cor($t->cor) }};"></span>{{ $t->nome }}@if($t->vlan) <small>(VLAN {{ $t->vlan }})</small>@endif @else<span class="vazio">—</span>@endif
                            </td>
                            <td>
                                @if($c){{ $c->patchPanel?->nome }} · <strong>{{ $c->porta_patch_panel }}</strong>@if($c->patchPanel && $c->patchPanel->rack_id !== $rack->id) <small>(rack {{ $c->patchPanel->rack?->nome }})</small>@endif
                                @else<span class="vazio">não ligada</span>@endif
                            </td>
                            <td>{!! $v?->sala ? e($v->sala->nome) : '<span class="vazio">—</span>' !!}</td>
                            <td>
                                @if($v?->tipoPorta)<span class="sw" style="background: {{ $cor($v->tipoPorta->cor) }};"></span>{{ $v->tipoPorta->nome }}@else<span class="vazio">—</span>@endif
                            </td>
                            <td class="ok"><i></i></td>
                            <td class="anot"></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        @endforeach

        @if($patchPanels->isEmpty() && $switches->isEmpty())
            <p class="vazio">Este rack não tem patch panels nem switches cadastrados.</p>
        @endif

        <div class="obs">
            <h2>Observações gerais</h2>
            @for($i = 0; $i < 6; $i++)<div class="linha"></div>@endfor
        </div>
    </div>

    <script>
        (function () {
            const livres = document.getElementById('opt-livres');
            const paisagem = document.getElementById('opt-paisagem');
            livres.addEventListener('change', () => document.body.classList.toggle('sem-livres', !livres.checked));
            paisagem.addEventListener('change', () => {
                document.getElementById('estilo-pagina').textContent =
                    `@page { size: A4 ${paisagem.checked ? 'landscape' : 'portrait'}; margin: 10mm; }`;
                document.querySelector('.folha').style.width = paisagem.checked ? '297mm' : '';
            });
        })();
    </script>
</body>
</html>

@php
    $corPadrao = '#ef4444'; // mesma cor usada nas outras telas para ponto sem tipo

    $dadosPontos = $markers->map(fn ($m) => [
        'id' => $m->id,
        'x' => (float) $m->x,
        'y' => (float) $m->y,
        'nome' => optional(optional($m->patchPanel)->rack)->nome . '-' . optional($m->patchPanel)->nome . '-' . $m->porta,
        'cor' => optional($m->tipoPorta)->cor ?: $corPadrao,
        'tipo' => (string) ($m->tipo_porta_id ?? 0),
        'fs' => (int) ($m->fontsize ?: 12),
        'pos' => $m->label_position ?: 'right',
        'dx' => (float) ($m->label_dx ?? 2.2),
        'dy' => (float) ($m->label_dy ?? -1.6),
    ])->values();

    $dadosSalas = $salas->map(fn ($s) => [
        'id' => $s->id,
        'x' => (float) $s->x,
        'y' => (float) $s->y,
        'nome' => $s->nome,
        'fs' => (int) ($s->fontsize ?: 12),
    ])->values();

    $tipos = $markers->groupBy(fn ($m) => (string) ($m->tipo_porta_id ?? 0))
        ->map(fn ($grupo, $id) => [
            'id' => (string) $id,
            'nome' => optional($grupo->first()->tipoPorta)->nome ?? 'Não definido',
            'cor' => optional($grupo->first()->tipoPorta)->cor ?: $corPadrao,
            'qtde' => $grupo->count(),
        ])
        ->sortBy('nome')
        ->values();

    $urlImagem = route('plantas.show', ['predio' => $planta->predio_id, 'planta' => $planta]);
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $planta->name }} — Impressão</title>
    <style id="estilo-pagina">@page { size: A4 landscape; margin: 10mm; }</style>
    <style>
        :root { --folha-w: 297mm; --folha-h: 210mm; --margem: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #1e293b; background: #e2e8f0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        /* ---------- Barra de controles (só na tela) ---------- */
        .controles {
            position: sticky; top: 0; z-index: 10;
            display: flex; flex-wrap: wrap; gap: 10px 18px; align-items: center;
            padding: 10px 16px; background: #fff; border-bottom: 1px solid #cbd5e1;
            box-shadow: 0 2px 6px rgba(0,0,0,.06); font-size: 13px;
        }
        .controles label { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .controles select, .controles input[type=range] { font-size: 13px; }
        .controles .grupo { display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .controles .tipos { display: inline-flex; gap: 10px; flex-wrap: wrap; padding-left: 12px; border-left: 1px solid #e2e8f0; }
        .controles .acoes { margin-left: auto; display: inline-flex; gap: 8px; }
        .btn { border: 1px solid #cbd5e1; background: #f8fafc; color: #1e293b; border-radius: 6px; padding: 6px 12px; cursor: pointer; font-size: 13px; text-decoration: none; }
        .btn-primario { background: #0d6efd; border-color: #0d6efd; color: #fff; font-weight: bold; }
        .tri { display: inline-block; width: 0; height: 0; border-left: 6px solid transparent; border-right: 6px solid transparent; border-bottom: 11px solid var(--cor); }

        /* ---------- Folhas ---------- */
        .folhas { display: flex; flex-direction: column; align-items: center; gap: 16px; padding: 20px; }
        .folha {
            width: var(--folha-w); height: var(--folha-h);
            padding: var(--margem); background: #fff;
            box-shadow: 0 4px 16px rgba(0,0,0,.12);
            display: flex; flex-direction: column; gap: 4mm;
        }
        .cabecalho { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 0.6mm solid #0d6efd; padding-bottom: 2mm; }
        .cabecalho h1 { margin: 0; font-size: 15pt; color: #0d6efd; }
        .cabecalho .sub { font-size: 9pt; color: #475569; margin-top: 1mm; }
        .cabecalho .dir { text-align: right; font-size: 8pt; color: #64748b; line-height: 1.4; }
        .cabecalho .parte { font-size: 10pt; font-weight: bold; color: #1e293b; }

        .folha.tabela { height: auto; min-height: var(--folha-h); }
        .planta-box { flex: 1 1 0; min-height: 0; border: 0.3mm solid #cbd5e1; overflow: hidden; }
        .planta-box svg { display: block; width: 100%; height: 100%; }

        .legenda { display: flex; flex-wrap: wrap; gap: 2mm 6mm; font-size: 8.5pt; align-items: center; }
        .legenda .item { display: inline-flex; align-items: center; gap: 1.5mm; }
        .legenda .pin { width: 3mm; height: 3mm; }
        .mini-mapa { display: inline-flex; gap: 0.6mm; margin-left: auto; align-items: center; font-size: 7.5pt; color: #64748b; }
        .mini-mapa span { width: 6mm; height: 2.4mm; border: 0.25mm solid #94a3b8; display: inline-block; }
        .mini-mapa span.on { background: #0d6efd; border-color: #0d6efd; }

        /* ---------- Tabela ---------- */
        table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        th, td { border: 0.25mm solid #cbd5e1; padding: 1.2mm 2mm; text-align: left; }
        thead th { background: #f1f5f9; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
        .sala-row td { background: #e2e8f0; font-weight: bold; text-transform: uppercase; }
        .sala-row small { text-transform: none; font-weight: normal; color: #475569; }
        .num { text-align: right; white-space: nowrap; }
        tfoot td { background: #1e293b; color: #fff; font-weight: bold; }
        .badge { display: inline-flex; align-items: center; gap: 1.5mm; }

        .rodape-link { font-size: 8pt; color: #475569; }
        .rodape-link a { color: #0d6efd; }

        @media print {
            body { background: #fff; }
            .controles { display: none; }
            .folhas { padding: 0; gap: 0; display: block; }
            .folha { width: auto; height: var(--area-h); padding: 0; box-shadow: none; break-after: page; overflow: hidden; }
            .folha.tabela { height: auto; min-height: 0; overflow: visible; }
            .folha:last-child { break-after: auto; }
        }

        @media (max-width: 900px) {
            .folhas { padding: 8px; align-items: stretch; }
            .folha { width: 100%; height: auto; }
            .planta-box { flex: none; }
            .planta-box svg { height: auto; }
        }
    </style>
</head>
<body>

<div class="controles">
    <div class="grupo">
        <label>Papel
            <select id="papel">
                <option value="A4">A4</option>
                <option value="A3">A3</option>
            </select>
        </label>
        <label>Orientação
            <select id="orientacao">
                <option value="landscape">Paisagem</option>
                <option value="portrait">Retrato</option>
            </select>
        </label>
        <label>Dividir planta em
            <select id="partes">
                <option value="1">1 página</option>
                <option value="2">2 páginas</option>
                <option value="3">3 páginas</option>
                <option value="4">4 páginas</option>
            </select>
        </label>
        <label>Tamanho das marcações
            <input type="range" id="escala" min="0.4" max="3" step="0.1" value="1">
        </label>
        <label><input type="checkbox" id="mostrarSalas" checked> Salas</label>
        <label><input type="checkbox" id="mostrarTabela" checked> Tabela de pontos</label>
    </div>
    @if($tipos->count() > 1)
        <div class="tipos" id="filtroTipos">
            @foreach($tipos as $tipo)
                <label><input type="checkbox" value="{{ $tipo['id'] }}" checked>
                    <span class="tri" style="--cor: {{ $tipo['cor'] }}"></span>{{ $tipo['nome'] }}</label>
            @endforeach
        </div>
    @endif
    <div class="acoes">
        <a class="btn" href="{{ $publicUrl }}">&larr; Voltar</a>
        <button type="button" class="btn btn-primario" onclick="window.print()">🖨 Imprimir / Salvar PDF</button>
    </div>
</div>

<div class="folhas" id="folhas"></div>

{{--
    Área invisível com os MESMOS marcadores da tela (mesmos partials/CSS).
    Na tela, o x/y salvo é o centro do conjunto "ícone + nome"; aqui medimos onde
    o ícone realmente fica para posicioná-lo igual na impressão.
--}}
<div id="medicao" aria-hidden="true" style="--escala-marcadores: {{ $planta->escala_marcadores ?? 1 }};">
    @include('plantas.partials.markers', ['markers' => $markers])
    @include('salas.partials.markers', ['salas' => $salas])
</div>

<template id="tpl-tabela">
    <div class="folha tabela">
        <div class="cabecalho">
            <div>
                <h1>{{ $planta->name }} — Pontos</h1>
                <div class="sub">Prédio: {{ optional($planta->predio)->nome ?? '—' }}</div>
            </div>
            <div class="dir">Gerado em {{ now()->format('d/m/Y H:i') }}</div>
        </div>
        <table>
            <thead>
                <tr><th>Ponto (Rack-Patch-Porta)</th><th>Tipo</th><th class="num">Comprimento</th><th>Comentário</th></tr>
            </thead>
            <tbody>
                @forelse($markers->groupBy(fn ($m) => optional($m->sala)->nome ?? 'Sem sala definida') as $nomeSala => $pontosDaSala)
                    @php $salaObj = $pontosDaSala->first()?->sala; @endphp
                    <tr class="sala-row" data-sala>
                        <td colspan="4">{{ $nomeSala }}
                            @if($salaObj && $salaObj->descricao) <small>— {{ $salaObj->descricao }}</small>@endif
                        </td>
                    </tr>
                    @foreach($pontosDaSala as $ponto)
                        <tr data-tipo="{{ $ponto->tipo_porta_id ?? 0 }}" data-tamanho="{{ $ponto->tamanho ?? 0 }}">
                            <td><strong>{{ optional(optional($ponto->patchPanel)->rack)->nome }}-{{ optional($ponto->patchPanel)->nome }}-{{ $ponto->porta }}</strong></td>
                            <td><span class="badge"><span class="tri" style="--cor: {{ optional($ponto->tipoPorta)->cor ?: $corPadrao }}"></span>{{ optional($ponto->tipoPorta)->nome ?? 'Não definido' }}</span></td>
                            <td class="num">{{ $ponto->tamanho ? number_format($ponto->tamanho, 2, ',', '.') . ' m' : '—' }}</td>
                            <td>{{ $ponto->comentario ?: '—' }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="4">Nenhum ponto visível nesta planta.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td>Total de pontos: <span id="totalPontos">{{ $markers->count() }}</span></td>
                    <td class="num">Cabeamento:</td>
                    <td class="num" id="totalCabo">{{ number_format($markers->sum('tamanho'), 2, ',', '.') }} m</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <div class="rodape-link">Versão interativa: <a href="{{ $publicUrl }}">{{ $publicUrl }}</a></div>
    </div>
</template>

<script>
(function () {
    const VB = @json($viewBox);           // [minX, minY, largura, altura] do SVG original
    const ESCALA_PLANTA = @json((float) ($planta->escala_marcadores ?? 1)); // tamanho global das marcações
    const RECORTE = @json($planta->recorteValido()); // janela visível em % (ou null)
    const PONTOS = @json($dadosPontos);   // x/y em % da planta (mesmo dado das outras telas)
    const SALAS = @json($dadosSalas);
    const TIPOS = @json($tipos);
    const URL_IMAGEM = @json($urlImagem);
    const TITULO = @json($planta->name);
    const PREDIO = @json(optional($planta->predio)->nome ?? '—');
    const GERADO = @json(now()->format('d/m/Y H:i'));
    const SVGNS = 'http://www.w3.org/2000/svg';

    // ---- Posição real dos ícones, medida com o layout da tela ----
    // Largura de referência: na tela, a planta ocupa a largura da janela (menos margens),
    // então usamos a largura desta mesma janela.
    const LARGURA_REF = Math.max(1000, Math.min(document.documentElement.clientWidth, 2560) - 62);
    const ANCORAS = { ponto: {}, sala: {} };
    (function medir() {
        const box = document.getElementById('medicao');
        // com recorte, a planta inteira aparece ampliada na tela (a área recortada ocupa a largura)
        const larguraPlanta = RECORTE ? LARGURA_REF * 100 / RECORTE.w : LARGURA_REF;
        box.style.cssText = `position:absolute; left:-100000px; top:0; visibility:hidden; pointer-events:none;
            --escala-marcadores:${ESCALA_PLANTA};
            width:${larguraPlanta}px; height:${larguraPlanta * VB[3] / VB[2]}px;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;`;
        const base = box.getBoundingClientRect();
        box.querySelectorAll('.marker-item').forEach(m => {
            const icone = m.querySelector('.marker-icone') || m.querySelector('svg');
            if (!icone) return;
            const r = icone.getBoundingClientRect();
            const tipo = m.classList.contains('marker-sala') ? 'sala' : 'ponto';
            ANCORAS[tipo][m.dataset.id] = {
                x: (r.left + r.width / 2 - base.left) / base.width * 100,
                y: (r.top + r.height / 2 - base.top) / base.height * 100,
            };
        });
        box.remove();
    })();
    const ancora = (tipo, item) => ANCORAS[tipo][item.id] || { x: item.x, y: item.y };

    const PAPEIS = { A4: [297, 210], A3: [420, 297] }; // mm, em paisagem
    const MARGEM = 10; // mm
    const SOBREPOSICAO = 0.04; // cada parte repete 4% da vizinha, para não cortar marcações na emenda

    const $ = id => document.getElementById(id);
    const folhas = $('folhas');

    function el(tag, attrs, pai) {
        const e = document.createElementNS(SVGNS, tag);
        for (const k in attrs) e.setAttribute(k, attrs[k]);
        if (pai) pai.appendChild(e);
        return e;
    }

    function tiposVisiveis() {
        const box = $('filtroTipos');
        if (!box) return null; // um único tipo: sem filtro
        return new Set([...box.querySelectorAll('input:checked')].map(i => i.value));
    }

    // Desenha uma parte da planta (fatia horizontal do viewBox) com as marcações
    function desenharParte(indice, total, escala, mostrarSalas, filtro) {
        const [minX, minY, w, h] = VB;
        // área a imprimir: a planta inteira ou só o recorte salvo
        const rx = RECORTE ? minX + RECORTE.x / 100 * w : minX;
        const ry = RECORTE ? minY + RECORTE.y / 100 * h : minY;
        const rw = RECORTE ? RECORTE.w / 100 * w : w;
        const rh = RECORTE ? RECORTE.h / 100 * h : h;

        const larguraParte = rw / total;
        const extra = total > 1 ? larguraParte * SOBREPOSICAO : 0;
        const x0 = Math.max(rx, rx + indice * larguraParte - extra);
        const x1 = Math.min(rx + rw, rx + (indice + 1) * larguraParte + extra);
        const pw = x1 - x0;

        const svg = el('svg', { viewBox: `${x0} ${ry} ${pw} ${rh}`, preserveAspectRatio: 'xMidYMid meet' });
        el('image', { href: URL_IMAGEM, x: minX, y: minY, width: w, height: h, preserveAspectRatio: 'none' }, svg);

        // tamanho das marcações proporcional à largura impressa (fica legível em qualquer papel/divisão)
        const unidade = pw * 0.0085 * escala * ESCALA_PLANTA;

        if (mostrarSalas) {
            SALAS.forEach(s => {
                const a = ancora('sala', s);
                const cx = minX + a.x / 100 * w, cy = minY + a.y / 100 * h;
                if (cx < x0 - unidade * 10 || cx > x1 + unidade * 10) return;
                const f = unidade * (s.fs / 12) * 1.15;
                const g = el('g', { transform: `translate(${cx},${cy})` }, svg);
                el('path', {
                    d: 'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5z',
                    transform: `translate(${-f * 0.5},${-f * 0.5}) scale(${f / 24})`,
                    fill: '#2563eb', stroke: '#fff', 'stroke-width': 2,
                }, g);
                const t = el('text', {
                    x: f * 0.6, y: f * 0.35, 'font-size': f, 'font-family': 'Arial, sans-serif',
                    'font-weight': 'bold', fill: '#1d4ed8', stroke: '#fff', 'stroke-width': f * 0.22,
                    'paint-order': 'stroke', 'stroke-linejoin': 'round',
                }, g);
                t.textContent = s.nome;
            });
        }

        PONTOS.forEach(p => {
            if (filtro && !filtro.has(p.tipo)) return;
            const a = ancora('ponto', p);
            const cx = minX + a.x / 100 * w, cy = minY + a.y / 100 * h;
            if (cx < x0 - unidade * 10 || cx > x1 + unidade * 10) return;
            const f = unidade * (p.fs / 12);
            const s = f * 0.62; // "raio" do triângulo
            const g = el('g', { transform: `translate(${cx},${cy})` }, svg);
            el('polygon', {
                points: `0,${-s} ${-s * 0.9},${s * 0.75} ${s * 0.9},${s * 0.75}`,
                fill: p.cor, stroke: '#fff', 'stroke-width': f * 0.12, 'stroke-linejoin': 'round',
            }, g);

            let ax = s + f * 0.2, ay = f * 0.35, anchor = 'start';
            if (p.pos === 'livre') {
                // rótulo solto, ligado ao ícone por uma linha guia
                ax = p.dx * f; ay = p.dy * f; anchor = 'middle';
                el('line', {
                    x1: 0, y1: 0, x2: ax, y2: ay, stroke: '#334155',
                    'stroke-width': f * 0.07, 'stroke-dasharray': `${f * 0.25} ${f * 0.17}`,
                }, g).parentNode.insertBefore(g.lastChild, g.firstChild);
                ay += f * 0.35; // centraliza o texto verticalmente no ponto do rótulo
            } else if (p.pos === 'left') { ax = -(s + f * 0.2); anchor = 'end'; }
            else if (p.pos === 'top') { ax = 0; ay = -(s + f * 0.25); anchor = 'middle'; }
            else if (p.pos === 'bottom') { ax = 0; ay = s + f * 1.05; anchor = 'middle'; }

            const t = el('text', {
                x: ax, y: ay, 'text-anchor': anchor, 'font-size': f, 'font-family': 'Arial, sans-serif',
                'font-weight': 'bold', fill: '#0f172a', stroke: '#fff', 'stroke-width': f * 0.22,
                'paint-order': 'stroke', 'stroke-linejoin': 'round',
            }, g);
            t.textContent = p.nome;
        });

        return svg;
    }

    function legenda(filtro, mostrarSalas, indice, total) {
        const div = document.createElement('div');
        div.className = 'legenda';
        TIPOS.forEach(t => {
            if (filtro && !filtro.has(t.id)) return;
            const s = document.createElement('span');
            s.className = 'item';
            s.innerHTML = '<span class="tri"></span>';
            s.firstChild.style.setProperty('--cor', t.cor);
            s.append(`${t.nome} (${t.qtde})`);
            div.appendChild(s);
        });
        if (mostrarSalas && SALAS.length) {
            const s = document.createElement('span');
            s.className = 'item';
            s.innerHTML = '<svg class="pin" viewBox="0 0 24 24"><path fill="#2563eb" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>';
            s.append('Sala');
            div.appendChild(s);
        }
        if (total > 1) {
            const m = document.createElement('span');
            m.className = 'mini-mapa';
            m.append('Parte ');
            for (let i = 0; i < total; i++) {
                const b = document.createElement('span');
                if (i === indice) b.className = 'on';
                m.appendChild(b);
            }
            div.appendChild(m);
        }
        return div;
    }

    function cabecalho(indice, total) {
        const div = document.createElement('div');
        div.className = 'cabecalho';
        const esq = document.createElement('div');
        const h1 = document.createElement('h1'); h1.textContent = TITULO;
        const sub = document.createElement('div'); sub.className = 'sub'; sub.textContent = 'Prédio: ' + PREDIO;
        esq.append(h1, sub);
        const dir = document.createElement('div');
        dir.className = 'dir';
        if (total > 1) {
            const p = document.createElement('div'); p.className = 'parte';
            p.textContent = `Parte ${indice + 1} de ${total}`;
            dir.appendChild(p);
        }
        dir.append('Gerado em ' + GERADO);
        div.append(esq, dir);
        return div;
    }

    function atualizarTabela(filtro) {
        const tab = folhas.querySelector('.folha.tabela');
        if (!tab) return;
        let total = 0, cabo = 0;
        let salaAtual = null, salaTemPonto = false;
        const fecharSala = () => { if (salaAtual) salaAtual.style.display = salaTemPonto ? '' : 'none'; };
        tab.querySelectorAll('tbody tr').forEach(tr => {
            if (tr.hasAttribute('data-sala')) { fecharSala(); salaAtual = tr; salaTemPonto = false; return; }
            if (!tr.hasAttribute('data-tipo')) return;
            const ok = !filtro || filtro.has(tr.dataset.tipo);
            tr.style.display = ok ? '' : 'none';
            if (ok) { total++; cabo += parseFloat(tr.dataset.tamanho) || 0; salaTemPonto = true; }
        });
        fecharSala();
        tab.querySelector('#totalPontos').textContent = total;
        tab.querySelector('#totalCabo').textContent =
            cabo.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' m';
    }

    function render() {
        const papel = $('papel').value;
        const orient = $('orientacao').value;
        const total = Number($('partes').value);
        const escala = Number($('escala').value);
        const mostrarSalas = $('mostrarSalas').checked;
        const filtro = tiposVisiveis();

        let [fw, fh] = PAPEIS[papel];
        if (orient === 'portrait') [fw, fh] = [fh, fw];
        $('estilo-pagina').textContent = `@page { size: ${papel} ${orient}; margin: ${MARGEM}mm; }`;
        const root = document.documentElement.style;
        root.setProperty('--folha-w', fw + 'mm');
        root.setProperty('--folha-h', fh + 'mm');
        root.setProperty('--area-h', (fh - 2 * MARGEM - 1) + 'mm');

        folhas.innerHTML = '';
        for (let i = 0; i < total; i++) {
            const folha = document.createElement('div');
            folha.className = 'folha';
            const box = document.createElement('div');
            box.className = 'planta-box';
            box.appendChild(desenharParte(i, total, escala, mostrarSalas, filtro));
            folha.append(cabecalho(i, total), box, legenda(filtro, mostrarSalas, i, total));
            folhas.appendChild(folha);
        }

        if ($('mostrarTabela').checked) {
            folhas.appendChild($('tpl-tabela').content.cloneNode(true));
            atualizarTabela(filtro);
        }
    }

    ['papel', 'orientacao', 'partes', 'escala', 'mostrarSalas', 'mostrarTabela'].forEach(id =>
        $(id).addEventListener('input', render));
    const filtroBox = $('filtroTipos');
    if (filtroBox) filtroBox.addEventListener('change', render);

    // Sugestão inicial: plantas muito largas ficam melhores divididas
    const proporcao = (VB[2] * (RECORTE ? RECORTE.w : 100)) / (VB[3] * (RECORTE ? RECORTE.h : 100));
    if (proporcao > 3.2) $('partes').value = proporcao > 5 ? '3' : '2';

    render();
})();
</script>
</body>
</html>

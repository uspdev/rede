{{--
    Conexões (patch cords) entre portas de switch e portas de patch panel.
    Depende dos atributos data-kind / data-dev / data-porta / data-label / data-tip
    das portas desenhadas em racks.partials.equipment e racks.partials.patch_panel.
--}}
@php
    $conexoesRack = \App\Models\Conexao::with('equipamento', 'patchPanel')
        ->where(function ($q) use ($rack) {
            $q->whereIn('equipamento_id', $rack->equipamentos()->pluck('id'))
              ->orWhereIn('patch_panel_id', $rack->patchPanels()->pluck('id'));
        })
        ->get()
        ->map->paraDiagrama()
        ->values();

    // Para o formulário de vínculo da porta do patch panel
    $salasPredio = \App\Models\Sala::where('predio_id', $rack->predio_id)->orderBy('nome')->get(['id', 'nome', 'descricao']);
    $tiposPortaForm = \App\Models\TipoPorta::orderBy('nome')->get(['id', 'nome', 'cor']);
    // Para o formulário de tipo da porta do switch
    $tiposPortaSwitchForm = \App\Models\TipoPortaSwitch::orderBy('nome')->get(['id', 'nome', 'cor', 'vlan']);
@endphp

<style>
    .net-legend { display: flex; flex-wrap: wrap; gap: 6px 16px; font-size: 12px; margin-top: 12px; align-items: center; }
    .net-legend-item { display: inline-flex; align-items: center; gap: 6px; }
    .net-swatch { width: 14px; height: 14px; border-radius: 3px; border: 1px solid rgba(0,0,0,.35); display: inline-block; }
    .net-legend-led { width: 8px; height: 8px; border-radius: 50%; display: inline-block; border: 1px solid rgba(0,0,0,.25); box-shadow: 0 0 4px #2ecc71; }

    .net-toolbar {
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        padding: 8px 12px; margin-bottom: 12px;
        border: 1px solid #dee2e6; border-radius: 6px; background: #f8f9fa;
        transition: background .15s, border-color .15s;
    }
    .net-toolbar.is-on { background: #e7f1ff; border-color: #9ec5fe; }
    .net-toolbar .net-hint { font-size: 13px; color: #495057; }
    .net-toolbar .net-sel-chip {
        font-size: 12px; background: #0d6efd; color: #fff; border-radius: 999px; padding: 2px 10px; display: none;
    }

    /* LED de "conectado" ao lado do número */
    .sw-num i, .pp-num i { width: 5px !important; height: 5px !important; }
    .net-port.is-conn .sw-num i,
    .net-port.is-conn .pp-num i { background: #39ff88; box-shadow: 0 0 2px #39ff88, 0 0 5px #2ecc71; }
    /* o par em destaque pisca o LED */
    .net-port.is-hl .sw-num i,
    .net-port.is-hl .pp-num i { animation: net-blink .5s steps(2, start) infinite; }
    @keyframes net-blink { to { visibility: hidden; } }

    /* Switch: porta conectada assume a cor da porta do patch panel */
    .sw-port.is-conn .sw-jack { background: var(--net-cor, linear-gradient(180deg, #b8bec4, #8a9198)); }

    /* Destaque do par ao passar o mouse */
    .net-port.is-hl .sw-jack,
    .net-port.is-hl .pp-jack { transform: scale(1.3); box-shadow: 0 0 0 2px #ffc107, 0 0 8px #ffc107; position: relative; z-index: 2; }

    .pp-port.net-port, .sw-port.net-port { cursor: pointer; }

    /* Modo conectar */
    .net-on .net-port { cursor: pointer; }
    .net-port.is-sel .sw-jack,
    .net-port.is-sel .pp-jack { box-shadow: 0 0 0 2px #0d6efd, 0 0 10px #0d6efd; animation: net-pulse 1s ease-in-out infinite; position: relative; z-index: 2; }
    @keyframes net-pulse { 50% { transform: scale(1.25); } }
    .net-on.net-sel-sw .net-port[data-kind="sw"]:not(.is-sel),
    .net-on.net-sel-pp .net-port[data-kind="pp"]:not(.is-sel) { opacity: .55; }
    .net-on.net-sel-sw .net-port[data-kind="pp"].is-conn,
    .net-on.net-sel-pp .net-port[data-kind="sw"].is-conn { opacity: .25; }

    /* Cabos de um switch selecionado */
    #net-diagramas { position: relative; }
    .sw-unit .sw-chassis { cursor: pointer; transition: box-shadow .15s; }
    .sw-unit .sw-chassis:hover { box-shadow: 0 0 0 2px rgba(13,110,253,.35), 0 2px 4px rgba(0,0,0,.25); }
    .sw-unit.is-cabos .sw-chassis { box-shadow: 0 0 0 3px #0d6efd, 0 2px 8px rgba(13,110,253,.35); }
    .sw-unit.is-cabos .sw-title span::after { content: " · mostrando patch cords"; font-weight: 400; color: #0d6efd; font-size: 12px; }
    .sw-ver-cabos .rot-ocultar { display: none; }
    .sw-unit.is-cabos .sw-ver-cabos { background: #0d6efd; color: #fff; }
    .sw-unit.is-cabos .sw-ver-cabos .rot-mostrar { display: none; }
    .sw-unit.is-cabos .sw-ver-cabos .rot-ocultar { display: inline; }
    .net-cabos { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; z-index: 5; overflow: visible; }
    .net-cabos path.halo { fill: none; stroke: rgba(0,0,0,.55); stroke-width: 5; stroke-linecap: round; }
    .net-cabos path.fio  { fill: none; stroke-width: 2.5; stroke-linecap: round; }
    .net-cabos circle { stroke: #fff; stroke-width: 1.5; }
    .net-cabos path.fio { stroke-dasharray: var(--len); stroke-dashoffset: var(--len); animation: net-draw .5s ease-out forwards; }
    /* cabo provisório enquanto escolhe a segunda porta */
    .net-cabos path.elastico { fill: none; stroke-width: 3; stroke-linecap: round; stroke-dasharray: 7 5; opacity: .9; animation: net-formiga .6s linear infinite; }
    .net-cabos path.elastico-halo { fill: none; stroke: rgba(0,0,0,.35); stroke-width: 5; stroke-linecap: round; }
    .net-cabos circle.elastico-ponta { stroke: #fff; stroke-width: 1.5; }
    @keyframes net-formiga { to { stroke-dashoffset: -12; } }
    /* faixa invisível sobre o cabo para o hover */
    .net-cabos path.alvo { fill: none; stroke: transparent; stroke-width: 10; pointer-events: stroke; cursor: help; }
    .net-cabos g.cabo.hover path.fio { stroke-width: 4.5; }
    .net-cabos g.cabo.hover path.halo { stroke-width: 7.5; }
    @keyframes net-draw { to { stroke-dashoffset: 0; } }

    /* Modal de vínculo da porta do patch panel */
    .pm-fundo { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); z-index: 1100; display: none; align-items: center; justify-content: center; padding: 16px; }
    .pm-fundo.show { display: flex; }
    .pm-caixa { background: #fff; border-radius: 10px; width: 100%; max-width: 480px; max-height: 92vh; overflow-y: auto; box-shadow: 0 12px 40px rgba(0,0,0,.25); }
    .pm-cab { padding: 14px 18px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .pm-cab h3 { font-size: 16px; margin: 0; }
    .pm-cab small { color: #64748b; }
    .pm-fechar { border: 0; background: none; font-size: 22px; line-height: 1; color: #64748b; cursor: pointer; }
    .pm-corpo { padding: 14px 18px; display: flex; flex-direction: column; gap: 12px; }
    .pm-corpo label.rot { font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block; }
    .pm-salas { border: 1px solid #cbd5e1; border-radius: 6px; }
    .pm-salas input { border: 0; border-bottom: 1px solid #e2e8f0; border-radius: 6px 6px 0 0; width: 100%; padding: 6px 10px; font-size: 13px; outline: none; }
    .pm-salas ul { list-style: none; margin: 0; padding: 4px; max-height: 170px; overflow-y: auto; }
    .pm-salas li { padding: 5px 8px; border-radius: 4px; cursor: pointer; font-size: 13px; }
    .pm-salas li small { color: #64748b; }
    .pm-salas li:hover { background: #f1f5f9; }
    .pm-salas li.sel { background: #0d6efd; color: #fff; }
    .pm-salas li.sel small { color: #dbeafe; }
    .pm-tipos { display: flex; flex-wrap: wrap; gap: 6px; }
    .pm-tipos button { border: 1px solid #cbd5e1; background: #fff; border-radius: 999px; padding: 3px 10px 3px 6px; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .pm-tipos button i { width: 12px; height: 12px; border-radius: 3px; border: 1px solid rgba(0,0,0,.25); display: inline-block; }
    .pm-tipos button.sel { border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13,110,253,.25); font-weight: 600; }
    .pm-linha { display: flex; gap: 10px; }
    .pm-linha > div { flex: 1; }
    .pm-corpo input.form-control { font-size: 13px; }
    .pm-info { font-size: 12px; background: #f1f5f9; border-radius: 6px; padding: 6px 10px; color: #334155; }
    .pm-erro { font-size: 12px; color: #dc3545; display: none; }
    .pm-rodape { padding: 12px 18px; border-top: 1px solid #e2e8f0; display: flex; gap: 8px; align-items: center; }
    .pm-rodape .esp { flex: 1; }

    .net-tooltip {
        position: fixed; z-index: 1080; pointer-events: none;
        background: rgba(0,0,0,.88); color: #fff;
        padding: 5px 9px; border-radius: 5px; font-size: 12px; line-height: 1.4;
        opacity: 0; transition: opacity .12s; max-width: 340px;
    }
    .net-tooltip .net-tip-link { color: #7ee2a8; }
    .net-tooltip .net-tip-none { color: #adb5bd; }

    .net-toast {
        position: fixed; right: 20px; bottom: 20px; z-index: 1090;
        padding: 10px 14px; border-radius: 6px; color: #fff; font-size: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,.2);
        opacity: 0; transform: translateY(10px); transition: opacity .2s, transform .2s; pointer-events: none;
    }
    .net-toast.show { opacity: 1; transform: none; }
    .net-toast.ok { background: #198754; }
    .net-toast.err { background: #dc3545; }
    .net-toast.info { background: #343a40; }
</style>

<div class="net-tooltip" id="net-tooltip"></div>
<div class="net-tooltip" id="net-tooltip-2"></div>

<div class="pm-fundo" id="pm-fundo" role="dialog" aria-modal="true" aria-labelledby="pm-titulo">
    <div class="pm-caixa">
        <div class="pm-cab">
            <div>
                <h3 id="pm-titulo">Porta</h3>
                <small id="pm-sub"></small>
            </div>
            <button type="button" class="pm-fechar" id="pm-fechar" aria-label="Fechar">&times;</button>
        </div>
        <div class="pm-corpo">
            <div class="pm-info" id="pm-switch" style="display: none;"></div>
            <div>
                <label class="rot">Sala/Ambiente *</label>
                <div class="pm-salas">
                    <input type="search" id="pm-busca" placeholder="Buscar sala/ambiente…" autocomplete="off">
                    <ul id="pm-lista-salas">
                        @foreach($salasPredio as $sala)
                            <li data-id="{{ $sala->id }}" data-busca="{{ Str::lower($sala->nome . ' ' . $sala->descricao) }}">
                                {{ $sala->nome }} @if($sala->descricao)<small>— {{ $sala->descricao }}</small>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
                @if($salasPredio->isEmpty())
                    <small class="text-muted">Nenhuma sala/ambiente cadastrada neste prédio.</small>
                @endif
            </div>
            <div>
                <label class="rot">Tipo de porta</label>
                <div class="pm-tipos" id="pm-tipos">
                    <button type="button" data-id=""><i style="background: #198754;"></i>Sem tipo</button>
                    @foreach($tiposPortaForm as $tipo)
                        <button type="button" data-id="{{ $tipo->id }}"><i style="background: {{ $tipo->cor ?: '#198754' }};"></i>{{ $tipo->nome }}</button>
                    @endforeach
                </div>
            </div>
            <div class="pm-linha">
                <div>
                    <label class="rot" for="pm-comentario">Comentário</label>
                    <input type="text" class="form-control form-control-sm" id="pm-comentario" maxlength="255" placeholder="Ex.: atrás da mesa">
                </div>
                <div style="flex: 0 0 130px;">
                    <label class="rot" for="pm-tamanho">Comprimento (m)</label>
                    <input type="number" class="form-control form-control-sm" id="pm-tamanho" step="0.01" min="0">
                </div>
            </div>
            <div class="pm-erro" id="pm-erro"></div>
        </div>
        <div class="pm-rodape">
            <button type="button" class="btn btn-sm btn-outline-danger" id="pm-remover">Desvincular</button>
            <span class="esp"></span>
            <button type="button" class="btn btn-sm btn-secondary" id="pm-cancelar">Cancelar</button>
            <button type="button" class="btn btn-sm btn-primary" id="pm-salvar">Salvar</button>
        </div>
    </div>
</div>
<div class="pm-fundo" id="ps-fundo" role="dialog" aria-modal="true" aria-labelledby="ps-titulo">
    <div class="pm-caixa">
        <div class="pm-cab">
            <div>
                <h3 id="ps-titulo">Porta do switch</h3>
                <small>Tipo da porta do switch</small>
            </div>
            <button type="button" class="pm-fechar" id="ps-fechar" aria-label="Fechar">&times;</button>
        </div>
        <div class="pm-corpo">
            <div class="pm-info" id="ps-pp" style="display: none;"></div>
            <div>
                <label class="rot">Tipo de porta de switch</label>
                <div class="pm-tipos" id="ps-tipos">
                    <button type="button" data-id=""><i style="background: transparent;"></i>Sem tipo</button>
                    @foreach($tiposPortaSwitchForm as $tipo)
                        <button type="button" data-id="{{ $tipo->id }}"><i style="background: {{ $tipo->cor ?: 'transparent' }};"></i>{{ $tipo->nome }}@if($tipo->vlan) · VLAN {{ $tipo->vlan }}@endif</button>
                    @endforeach
                </div>
                @if($tiposPortaSwitchForm->isEmpty())
                    <small class="text-muted">Nenhum tipo cadastrado. <a href="{{ route('tipo-portas-switch.create') }}">Cadastrar tipo de porta de switch</a>.</small>
                @endif
            </div>
            <div class="pm-erro" id="ps-erro"></div>
        </div>
        <div class="pm-rodape">
            <span class="esp"></span>
            <button type="button" class="btn btn-sm btn-secondary" id="ps-cancelar">Cancelar</button>
            <button type="button" class="btn btn-sm btn-primary" id="ps-salvar">Salvar</button>
        </div>
    </div>
</div>
<div class="net-toast" id="net-toast"></div>

<script>
(function () {
    const conexoes = @json($conexoesRack);
    const URL_CONEXOES = @json(url('/conexoes'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const root = document.getElementById('net-diagramas');
    const toolbar = document.getElementById('net-toolbar');
    const toggle = document.getElementById('net-toggle');
    const hint = document.getElementById('net-hint');
    const chip = document.getElementById('net-sel-chip');
    const tip = document.getElementById('net-tooltip');
    const tip2 = document.getElementById('net-tooltip-2');
    let tipNoCabo = false;
    const toast = document.getElementById('net-toast');
    if (!root) return;

    const HINT_OFF = 'Clique numa porta do patch panel para vincular a uma sala/ambiente, ou numa porta do switch para definir o tipo. Use "Patch cords" (ou clique no chassi do switch) para ver os cabos.';
    const COR_CABO_PADRAO = '#5c7cfa'; // porta do patch panel sem sala vinculada
    const HINT_ON = 'Clique numa porta do switch e depois numa do patch panel (ou o contrário). Clique numa porta já conectada para desconectar. Esc para sair.';

    let modo = false;
    let selecionada = null;

    const portas = () => root.querySelectorAll('.net-port');
    const key = (kind, dev, porta) => `${kind}:${dev}:${porta}`;
    const portaEl = (kind, dev, porta) =>
        root.querySelector(`.net-port[data-kind="${kind}"][data-dev="${dev}"][data-porta="${porta}"]`);

    function conexaoDe(el) {
        const dev = Number(el.dataset.dev), porta = Number(el.dataset.porta);
        return conexoes.find(c => el.dataset.kind === 'sw'
            ? c.sw === dev && c.swPorta === porta
            : c.pp === dev && c.ppPorta === porta);
    }

    function parceiro(c, kind) {
        return kind === 'sw' ? portaEl('pp', c.pp, c.ppPorta) : portaEl('sw', c.sw, c.swPorta);
    }

    function descricaoParceiro(c, kind) {
        return kind === 'sw'
            ? `Patch panel ${c.ppLabel} · porta ${c.ppPorta}`
            : `${c.swLabel} · porta ${c.swPorta}`;
    }

    // Aplica o estado (conectado / cor) em todas as portas
    function pintar() {
        portas().forEach(el => {
            el.classList.remove('is-conn');
            el.style.removeProperty('--net-cor');
        });
        conexoes.forEach(c => {
            const sw = portaEl('sw', c.sw, c.swPorta);
            const pp = portaEl('pp', c.pp, c.ppPorta);
            if (sw) sw.classList.add('is-conn');
            if (pp) pp.classList.add('is-conn');
            if (sw && pp && pp.classList.contains('is-linked')) {
                sw.style.setProperty('--net-cor', getComputedStyle(pp).getPropertyValue('--pp-cor'));
            }
        });
        if (typeof desenharCabos === 'function' && swCabos !== null) desenharCabos(false);
    }

    // ---------- Cabos do switch selecionado ----------
    const SVGNS = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(SVGNS, 'svg');
    svg.setAttribute('class', 'net-cabos');
    root.appendChild(svg);
    let swCabos = null; // id do switch selecionado (apenas um)

    function centro(el) {
        const jack = el.querySelector('.sw-jack, .pp-jack') || el;
        const r = jack.getBoundingClientRect();
        const base = root.getBoundingClientRect();
        return { x: r.left + r.width / 2 - base.left, y: r.top + r.height / 2 - base.top };
    }

    // ---------- Cabo "elástico" no modo conectar ----------
    const svgTemp = document.createElementNS(SVGNS, 'svg');
    svgTemp.setAttribute('class', 'net-cabos');
    root.appendChild(svgTemp);

    function corDaPorta(el) {
        if (el.dataset.kind === 'sw') return el.dataset.tipoCor || COR_CABO_PADRAO;
        return el.classList.contains('is-linked') ? getComputedStyle(el).getPropertyValue('--pp-cor').trim() || COR_CABO_PADRAO : COR_CABO_PADRAO;
    }

    function curva(a, b) {
        // o cabo sai na vertical na direção da outra ponta, como um patch cord
        const desce = b.y > a.y ? 1 : -1;
        const k = Math.min(90, 25 + Math.abs(b.y - a.y) * 0.35 + Math.abs(b.x - a.x) * 0.08);
        return `M${a.x},${a.y} C${a.x},${a.y + desce * k} ${b.x},${b.y - desce * k} ${b.x},${b.y}`;
    }

    function desenharElastico(destino) {
        svgTemp.innerHTML = '';
        if (!modo || !selecionada || !destino) return;
        const a = centro(selecionada);
        const cor = corDaPorta(selecionada);
        const d = curva(a, destino);
        const halo = document.createElementNS(SVGNS, 'path');
        halo.setAttribute('d', d);
        halo.setAttribute('class', 'elastico-halo');
        const fio = document.createElementNS(SVGNS, 'path');
        fio.setAttribute('d', d);
        fio.setAttribute('class', 'elastico');
        fio.setAttribute('stroke', cor);
        svgTemp.append(halo, fio);
        [a, destino].forEach(p => {
            const c = document.createElementNS(SVGNS, 'circle');
            c.setAttribute('cx', p.x);
            c.setAttribute('cy', p.y);
            c.setAttribute('r', 4);
            c.setAttribute('fill', cor);
            c.setAttribute('class', 'elastico-ponta');
            svgTemp.appendChild(c);
        });
    }

    root.addEventListener('mousemove', e => {
        if (!modo || !selecionada) return;
        // encaixa numa porta livre do outro lado; senão segue o mouse
        const alvo = e.target.closest('.net-port');
        if (alvo && alvo.dataset.kind !== selecionada.dataset.kind && !conexaoDe(alvo)) {
            desenharElastico(centro(alvo));
        } else {
            const base = root.getBoundingClientRect();
            desenharElastico({ x: e.clientX - base.left, y: e.clientY - base.top });
        }
    });
    root.addEventListener('mouseleave', () => svgTemp.innerHTML = '');

    // Mostra (sem alternar) os patch cords de um switch
    function mostrarCabosDe(id) {
        swCabos = id;
        root.querySelectorAll('.sw-unit').forEach(u => u.classList.toggle('is-cabos', Number(u.dataset.dev) === id));
        desenharCabos(true);
    }

    function desenharCabos(animar) {
        svg.innerHTML = '';
        if (swCabos === null) return;
        const lista = conexoes.filter(c => c.sw === swCabos);
        let foraDoRack = 0;
        lista.forEach((c, i) => {
            const sw = portaEl('sw', c.sw, c.swPorta);
            const pp = portaEl('pp', c.pp, c.ppPorta);
            if (!sw || !pp) { foraDoRack++; return; }
            const a = centro(sw), b = centro(pp);
            // Cor do patch cord: tipo da porta do switch > tipo da porta do patch panel > padrão
            const cor = sw.dataset.tipoCor
                || (pp.classList.contains('is-linked') ? getComputedStyle(pp).getPropertyValue('--pp-cor').trim() : '')
                || COR_CABO_PADRAO;
            // O cabo sai da porta na vertical (para cima nas portas de cima, para baixo nas de baixo),
            // como um patch cord de verdade, e chega ao patch panel do mesmo jeito.
            // Patch panel acima do switch (layout padrão): o cabo sobe do switch e entra por baixo no patch panel
            const ppAcima = b.y < a.y;
            const saidaSw = ppAcima ? -1 : (sw.classList.contains('bottom') ? 1 : -1);
            const linhaPP = pp.closest('.pp-row');
            const saidaPP = ppAcima ? 1 : (linhaPP && linhaPP.previousElementSibling ? 1 : -1);
            const alcance = Math.min(90, 30 + Math.abs(b.x - a.x) * 0.12 + (i % 5) * 6);
            const d = `M${a.x},${a.y} C${a.x},${a.y + saidaSw * alcance} ${b.x},${b.y + saidaPP * alcance} ${b.x},${b.y}`;

            const grupo = document.createElementNS(SVGNS, 'g');
            grupo.setAttribute('class', 'cabo');
            const halo = document.createElementNS(SVGNS, 'path');
            halo.setAttribute('d', d);
            halo.setAttribute('class', 'halo');
            const fio = document.createElementNS(SVGNS, 'path');
            fio.setAttribute('d', d);
            fio.setAttribute('class', 'fio');
            fio.setAttribute('stroke', cor);
            const alvo = document.createElementNS(SVGNS, 'path');
            alvo.setAttribute('d', d);
            alvo.setAttribute('class', 'alvo');
            grupo.append(halo, fio, alvo);
            svg.appendChild(grupo);

            // Hover no patch cord: tipo da porta do switch e tipo da porta do patch panel
            // Hover no patch cord: mostra as caixas das duas portas, cada uma junto da sua porta
            alvo.addEventListener('mouseenter', () => {
                grupo.classList.add('hover');
                [sw, pp].forEach(x => x.classList.add('is-hl'));
                tipNoCabo = true;
                mostrarTipNaPorta(tip, sw);
                mostrarTipNaPorta(tip2, pp);
            });
            alvo.addEventListener('mouseleave', () => {
                grupo.classList.remove('hover');
                [sw, pp].forEach(x => x.classList.remove('is-hl'));
                tipNoCabo = false;
                tip.style.opacity = 0;
                tip2.style.opacity = 0;
            });
            const len = fio.getTotalLength();
            fio.style.setProperty('--len', len);
            if (!animar) fio.style.animation = 'none', fio.style.strokeDashoffset = 0;

            [a, b].forEach(p => {
                const ponto = document.createElementNS(SVGNS, 'circle');
                ponto.setAttribute('cx', p.x);
                ponto.setAttribute('cy', p.y);
                ponto.setAttribute('r', 3.5);
                ponto.setAttribute('fill', cor);
                svg.appendChild(ponto);
            });
        });
        return { total: lista.length, foraDoRack };
    }

    function selecionarSwitch(id) {
        swCabos = (swCabos === id) ? null : id;
        root.querySelectorAll('.sw-unit').forEach(u =>
            u.classList.toggle('is-cabos', Number(u.dataset.dev) === swCabos));
        const r = desenharCabos(true);
        if (r) {
            if (r.total === 0) toastMsg('Este switch não tem portas conectadas a patch panels.', 'info');
            else if (r.foraDoRack) toastMsg(`${r.foraDoRack} conexão(ões) vão para patch panels de outro rack.`, 'info');
        }
    }

    let raf = null;
    const redesenhar = () => {
        if (swCabos === null) return;
        cancelAnimationFrame(raf);
        raf = requestAnimationFrame(() => desenharCabos(false));
    };
    window.addEventListener('resize', redesenhar);
    root.querySelectorAll('.sw-wrap, .pp-wrap').forEach(w => w.addEventListener('scroll', redesenhar));

    function toastMsg(texto, tipo) {
        toast.textContent = texto;
        toast.className = 'net-toast show ' + tipo;
        clearTimeout(toastMsg._t);
        toastMsg._t = setTimeout(() => toast.classList.remove('show'), 2600);
    }

    function limparSelecao() {
        if (selecionada) selecionada.classList.remove('is-sel');
        selecionada = null;
        root.classList.remove('net-sel-sw', 'net-sel-pp');
        chip.style.display = 'none';
        svgTemp.innerHTML = '';
    }

    function setModo(on) {
        modo = on;
        limparSelecao();
        root.classList.toggle('net-on', on);
        toolbar.classList.toggle('is-on', on);
        toggle.classList.toggle('btn-primary', on);
        toggle.classList.toggle('btn-outline-primary', !on);
        toggle.innerHTML = on ? '<i class="fas fa-check"></i> Concluir' : '<i class="fas fa-link"></i> Conectar portas';
        hint.textContent = on ? HINT_ON : HINT_OFF;
    }

    async function api(method, url, body) {
        const r = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: body ? JSON.stringify(body) : undefined,
        });
        const data = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(data.message || 'Erro ao salvar');
        return data;
    }

    async function conectar(a, b) {
        const sw = a.dataset.kind === 'sw' ? a : b;
        const pp = a.dataset.kind === 'pp' ? a : b;
        try {
            const data = await api('POST', URL_CONEXOES, {
                equipamento_id: Number(sw.dataset.dev),
                porta_equipamento: Number(sw.dataset.porta),
                patch_panel_id: Number(pp.dataset.dev),
                porta_patch_panel: Number(pp.dataset.porta),
            });
            conexoes.push(data.conexao);
            pintar();
            mostrarCabosDe(Number(sw.dataset.dev));
            toastMsg(`Conectado: ${sw.dataset.label} porta ${sw.dataset.porta} ↔ ${pp.dataset.label} porta ${pp.dataset.porta}`, 'ok');
        } catch (e) {
            toastMsg(e.message, 'err');
        }
    }

    async function desconectar(c) {
        if (!confirm(`Desconectar ${c.swLabel} porta ${c.swPorta} ↔ patch panel ${c.ppLabel} porta ${c.ppPorta}?`)) return;
        try {
            await api('DELETE', `${URL_CONEXOES}/${c.id}`);
            conexoes.splice(conexoes.indexOf(c), 1);
            pintar();
            toastMsg('Conexão removida', 'ok');
        } catch (e) {
            toastMsg(e.message, 'err');
        }
    }

    function clique(el) {
        if (!modo) {
            if (el.dataset.kind === 'pp') abrirPorta(el);
            else abrirPortaSwitch(el);
            return;
        }
        const c = conexaoDe(el);

        if (c) {
            limparSelecao();
            desconectar(c);
            return;
        }
        if (!selecionada || selecionada.dataset.kind === el.dataset.kind) {
            // primeira porta (ou troca de porta do mesmo lado)
            if (selecionada === el) { limparSelecao(); return; }
            limparSelecao();
            selecionada = el;
            el.classList.add('is-sel');
            root.classList.add(el.dataset.kind === 'sw' ? 'net-sel-sw' : 'net-sel-pp');
            chip.textContent = `${el.dataset.kind === 'sw' ? '' : 'Patch panel '}${el.dataset.label} · porta ${el.dataset.porta} → escolha a outra ponta`;
            chip.style.display = 'inline-block';
            return;
        }
        const a = selecionada;
        limparSelecao();
        conectar(a, el);
    }

    // Eventos (delegados)
    root.addEventListener('click', e => {
        const btnCabos = e.target.closest('.sw-ver-cabos');
        if (btnCabos) { selecionarSwitch(Number(btnCabos.dataset.dev)); return; }
        const el = e.target.closest('.net-port');
        if (el) { clique(el); return; }
        const chassis = e.target.closest('.sw-chassis');
        if (chassis) selecionarSwitch(Number(chassis.closest('.sw-unit').dataset.dev));
    });

    let hl = [];
    // Conteúdo da caixa de uma porta (usado no hover da porta e no hover do patch cord)
    function conteudoTip(el) {
        const c = conexaoDe(el);
        const base = document.createElement('div');
        base.textContent = el.dataset.tip;
        let html = base.innerHTML;
        if (el.dataset.kind === 'sw' && el.dataset.tipoNome) {
            const t = document.createElement('span');
            t.textContent = 'Tipo: ' + el.dataset.tipoNome;
            html += '<br>' + t.innerHTML;
        }
        if (!c) {
            html += '<br><span class="net-tip-none">' + (el.dataset.kind === 'pp' ? 'Não está ligada a switch' : 'Não está ligada a patch panel') + '</span>';
        } else {
            const outro = document.createElement('span');
            outro.textContent = (el.dataset.kind === 'pp' ? 'No switch: ' : 'No patch panel: ') + descricaoParceiro(c, el.dataset.kind);
            html += '<br><span class="net-tip-link">● ' + outro.innerHTML + '</span>';
        }
        return html;
    }

    // Posiciona a caixa junto da porta: acima no patch panel, abaixo no switch
    function mostrarTipNaPorta(caixa, el) {
        caixa.innerHTML = conteudoTip(el);
        caixa.style.opacity = 1;
        const r = (el.querySelector('.sw-jack, .pp-jack') || el).getBoundingClientRect();
        const w = caixa.offsetWidth, h = caixa.offsetHeight;
        let left = Math.max(8, Math.min(r.left + r.width / 2 - w / 2, window.innerWidth - w - 8));
        let top = el.dataset.kind === 'pp' ? r.top - h - 10 : r.bottom + 10;
        if (top < 8) top = r.bottom + 10;
        if (top + h > window.innerHeight - 8) top = r.top - h - 10;
        caixa.style.left = left + 'px';
        caixa.style.top = top + 'px';
    }

    root.addEventListener('mouseover', e => {
        const el = e.target.closest('.net-port');
        if (!el) return;
        hl.forEach(x => x.classList.remove('is-hl'));
        hl = [];
        const c = conexaoDe(el);
        if (c) {
            const p = parceiro(c, el.dataset.kind);
            hl = [el];
            if (p) hl.push(p);
            hl.forEach(x => x.classList.add('is-hl'));
        }
        tip.innerHTML = conteudoTip(el);
        tip.style.opacity = 1;
    });
    root.addEventListener('mousemove', e => {
        if (tipNoCabo) return; // caixas presas às portas enquanto o mouse está no patch cord
        tip.style.left = (e.clientX + 14) + 'px';
        tip.style.top = (e.clientY + 14) + 'px';
    });
    root.addEventListener('mouseout', e => {
        const el = e.target.closest('.net-port');
        if (!el || el.contains(e.relatedTarget)) return;
        tip.style.opacity = 0;
        hl.forEach(x => x.classList.remove('is-hl'));
        hl = [];
    });

    if (toggle) toggle.addEventListener('click', () => setModo(!modo));
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && fundoModal.classList.contains('show')) { fecharPorta(); return; }
        if (e.key === 'Escape' && fundoSw.classList.contains('show')) { fecharPortaSwitch(); return; }
        if (e.key === 'Escape' && modo) {
            if (selecionada) limparSelecao(); else setModo(false);
        }
    });

    // ---------- Formulário da porta do patch panel (sala, tipo, comentário...) ----------
    const URL_PORTA = @json(url('/patch-panels')) ;
    const fundoModal = document.getElementById('pm-fundo');
    const listaSalas = document.getElementById('pm-lista-salas');
    const buscaSala = document.getElementById('pm-busca');
    const erroModal = document.getElementById('pm-erro');
    let portaAtual = null, salaSel = null, tipoSel = '';

    function marcarSala(id) {
        salaSel = id ? String(id) : null;
        listaSalas.querySelectorAll('li').forEach(li => li.classList.toggle('sel', li.dataset.id === salaSel));
    }
    function marcarTipo(id) {
        tipoSel = id ? String(id) : '';
        document.querySelectorAll('#pm-tipos button').forEach(b => b.classList.toggle('sel', b.dataset.id === tipoSel));
    }

    function abrirPorta(el) {
        portaAtual = el;
        const vinculada = el.classList.contains('is-linked');
        document.getElementById('pm-titulo').textContent = `Patch panel ${el.dataset.label} · porta ${el.dataset.porta}`;
        document.getElementById('pm-sub').textContent = vinculada ? 'Vinculada a uma sala/ambiente — edite ou desvincule' : 'Porta livre — escolha a sala/ambiente';
        buscaSala.value = '';
        listaSalas.querySelectorAll('li').forEach(li => li.style.display = '');
        marcarSala(el.dataset.salaId);
        marcarTipo(el.dataset.tipoId);
        document.getElementById('pm-comentario').value = el.dataset.comentario || '';
        document.getElementById('pm-tamanho').value = el.dataset.tamanho || '';
        document.getElementById('pm-remover').style.display = vinculada ? '' : 'none';
        erroModal.style.display = 'none';

        const c = conexaoDe(el);
        const info = document.getElementById('pm-switch');
        info.style.display = c ? '' : 'none';
        if (c) info.textContent = `🔗 Ligada ao switch ${c.swLabel}, porta ${c.swPorta}`;

        tip.style.opacity = 0;
        fundoModal.classList.add('show');
        const sel = listaSalas.querySelector('li.sel');
        if (sel) sel.scrollIntoView({ block: 'nearest' });
        setTimeout(() => buscaSala.focus(), 50);
    }
    function fecharPorta() {
        fundoModal.classList.remove('show');
        portaAtual = null;
    }

    buscaSala.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        listaSalas.querySelectorAll('li').forEach(li => li.style.display = !q || li.dataset.busca.includes(q) ? '' : 'none');
    });
    buscaSala.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const primeira = [...listaSalas.querySelectorAll('li')].find(li => li.style.display !== 'none');
            if (primeira) marcarSala(primeira.dataset.id);
        }
    });
    listaSalas.addEventListener('click', e => { const li = e.target.closest('li'); if (li) marcarSala(li.dataset.id); });
    document.getElementById('pm-tipos').addEventListener('click', e => { const b = e.target.closest('button'); if (b) marcarTipo(b.dataset.id); });
    document.getElementById('pm-fechar').addEventListener('click', fecharPorta);
    document.getElementById('pm-cancelar').addEventListener('click', fecharPorta);
    fundoModal.addEventListener('click', e => { if (e.target === fundoModal) fecharPorta(); });

    document.getElementById('pm-salvar').addEventListener('click', async function () {
        if (!portaAtual) return;
        if (!salaSel) { erroModal.textContent = 'Escolha a sala/ambiente.'; erroModal.style.display = 'block'; return; }
        this.disabled = true;
        try {
            const tamanho = document.getElementById('pm-tamanho').value;
            await api('PUT', `${URL_PORTA}/${portaAtual.dataset.dev}/porta/${portaAtual.dataset.porta}`, {
                sala_id: Number(salaSel),
                tipo_porta_id: tipoSel ? Number(tipoSel) : null,
                comentario: document.getElementById('pm-comentario').value || null,
                tamanho: tamanho === '' ? null : Number(tamanho),
            });
            window.location.reload();
        } catch (e) {
            erroModal.textContent = e.message;
            erroModal.style.display = 'block';
            this.disabled = false;
        }
    });

    document.getElementById('pm-remover').addEventListener('click', async function () {
        if (!portaAtual) return;
        let msg = `Desvincular a porta ${portaAtual.dataset.porta} do patch panel ${portaAtual.dataset.label} da sala/ambiente?`;
        if (portaAtual.dataset.marcada === '1') msg += '\n\nA marcação desta porta na planta também será removida.';
        if (!confirm(msg)) return;
        try {
            await api('DELETE', `${URL_PORTA}/${portaAtual.dataset.dev}/porta/${portaAtual.dataset.porta}`);
            window.location.reload();
        } catch (e) {
            erroModal.textContent = e.message;
            erroModal.style.display = 'block';
        }
    });

    // ---------- Formulário do tipo da porta do switch ----------
    const URL_EQUIP = @json(url('/equipamentos'));
    const fundoSw = document.getElementById('ps-fundo');
    const erroSw = document.getElementById('ps-erro');
    let portaSwAtual = null, tipoSwSel = '';

    function marcarTipoSw(id) {
        tipoSwSel = id ? String(id) : '';
        document.querySelectorAll('#ps-tipos button').forEach(b => b.classList.toggle('sel', b.dataset.id === tipoSwSel));
    }
    function abrirPortaSwitch(el) {
        portaSwAtual = el;
        document.getElementById('ps-titulo').textContent = `${el.dataset.label} · porta ${el.dataset.porta}`;
        marcarTipoSw(el.dataset.tipoId);
        erroSw.style.display = 'none';
        const c = conexaoDe(el);
        const info = document.getElementById('ps-pp');
        info.style.display = c ? '' : 'none';
        if (c) info.textContent = `🔗 Ligada ao patch panel ${c.ppLabel}, porta ${c.ppPorta}`;
        tip.style.opacity = 0;
        fundoSw.classList.add('show');
    }
    function fecharPortaSwitch() {
        fundoSw.classList.remove('show');
        portaSwAtual = null;
    }
    document.getElementById('ps-tipos').addEventListener('click', e => { const b = e.target.closest('button'); if (b) marcarTipoSw(b.dataset.id); });
    document.getElementById('ps-fechar').addEventListener('click', fecharPortaSwitch);
    document.getElementById('ps-cancelar').addEventListener('click', fecharPortaSwitch);
    fundoSw.addEventListener('click', e => { if (e.target === fundoSw) fecharPortaSwitch(); });
    document.getElementById('ps-salvar').addEventListener('click', async function () {
        if (!portaSwAtual) return;
        this.disabled = true;
        try {
            await api('PUT', `${URL_EQUIP}/${portaSwAtual.dataset.dev}/porta/${portaSwAtual.dataset.porta}/tipo`, {
                tipo_porta_switch_id: tipoSwSel ? Number(tipoSwSel) : null,
            });
            window.location.reload();
        } catch (e) {
            erroSw.textContent = e.message;
            erroSw.style.display = 'block';
            this.disabled = false;
        }
    });

    hint.textContent = HINT_OFF;
    pintar();
})();
</script>

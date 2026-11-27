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
    .sw-unit.is-cabos .sw-title span::after { content: " · mostrando cabos"; font-weight: 400; color: #0d6efd; font-size: 12px; }
    .net-cabos { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; z-index: 5; overflow: visible; }
    .net-cabos path.halo { fill: none; stroke: rgba(0,0,0,.55); stroke-width: 5; stroke-linecap: round; }
    .net-cabos path.fio  { fill: none; stroke-width: 2.5; stroke-linecap: round; }
    .net-cabos circle { stroke: #fff; stroke-width: 1.5; }
    .net-cabos path.fio { stroke-dasharray: var(--len); stroke-dashoffset: var(--len); animation: net-draw .5s ease-out forwards; }
    @keyframes net-draw { to { stroke-dashoffset: 0; } }

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
    const toast = document.getElementById('net-toast');
    if (!root) return;

    const HINT_OFF = 'Passe o mouse numa porta para ver a conexão. Clique num switch para ver os cabos até o patch panel.';
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
            const cor = pp.classList.contains('is-linked')
                ? getComputedStyle(pp).getPropertyValue('--pp-cor').trim()
                : COR_CABO_PADRAO;
            // O cabo sai da porta na vertical (para cima nas portas de cima, para baixo nas de baixo),
            // como um patch cord de verdade, e chega ao patch panel do mesmo jeito.
            const saidaSw = sw.classList.contains('bottom') ? 1 : -1;
            const linhaPP = pp.closest('.pp-row');
            const saidaPP = linhaPP && linhaPP.previousElementSibling ? 1 : -1;
            const alcance = Math.min(90, 30 + Math.abs(b.x - a.x) * 0.12 + (i % 5) * 6);
            const d = `M${a.x},${a.y} C${a.x},${a.y + saidaSw * alcance} ${b.x},${b.y + saidaPP * alcance} ${b.x},${b.y}`;

            const halo = document.createElementNS(SVGNS, 'path');
            halo.setAttribute('d', d);
            halo.setAttribute('class', 'halo');
            const fio = document.createElementNS(SVGNS, 'path');
            fio.setAttribute('d', d);
            fio.setAttribute('class', 'fio');
            fio.setAttribute('stroke', cor);
            svg.append(halo, fio);
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
        if (!modo) return;
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
        const el = e.target.closest('.net-port');
        if (el) { clique(el); return; }
        const chassis = e.target.closest('.sw-chassis');
        if (chassis) selecionarSwitch(Number(chassis.closest('.sw-unit').dataset.dev));
    });

    let hl = [];
    root.addEventListener('mouseover', e => {
        const el = e.target.closest('.net-port');
        if (!el) return;
        hl.forEach(x => x.classList.remove('is-hl'));
        hl = [];
        const c = conexaoDe(el);
        let html = '';
        const base = document.createElement('div');
        base.textContent = el.dataset.tip;
        html = base.innerHTML;
        if (!c) {
            html += '<br><span class="net-tip-none">' + (el.dataset.kind === 'pp' ? 'Não está ligada a switch' : 'Não está ligada a patch panel') + '</span>';
        }
        if (c) {
            const outro = document.createElement('span');
            outro.textContent = (el.dataset.kind === 'pp' ? 'No switch: ' : 'No patch panel: ') + descricaoParceiro(c, el.dataset.kind);
            html += '<br><span class="net-tip-link">● ' + outro.innerHTML + '</span>';
            const p = parceiro(c, el.dataset.kind);
            hl = [el];
            if (p) hl.push(p);
            hl.forEach(x => x.classList.add('is-hl'));
        }
        tip.innerHTML = html;
        tip.style.opacity = 1;
    });
    root.addEventListener('mousemove', e => {
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
        if (e.key === 'Escape' && modo) {
            if (selecionada) limparSelecao(); else setModo(false);
        }
    });

    hint.textContent = HINT_OFF;
    pintar();
})();
</script>

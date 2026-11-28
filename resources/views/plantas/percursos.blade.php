@extends('main')

@section('content')
<script src="https://unpkg.com/@panzoom/panzoom@4.5.1/dist/panzoom.min.js"></script>
@php
    $tipos = \App\Models\PlantaPercurso::TIPOS;
    $recorte = $planta->estilosRecorte();
@endphp
<style>
    .pc-topo { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
    .pc-barra { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 10px; margin-bottom: 8px; }
    .pc-barra .sep { width: 1px; align-self: stretch; background: #cbd5e1; margin: 0 4px; }
    .pc-tipo-btn { display: inline-flex; align-items: center; gap: 6px; }
    .pc-tipo-btn .amostra { width: 26px; height: 0; border-top-style: solid; border-radius: 3px; display: inline-block; }
    .pc-tipo-btn.ativo { box-shadow: 0 0 0 3px rgba(13,110,253,.35); font-weight: 600; }
    .pc-dica { font-size: 12px; color: #475569; }
    .pc-dica kbd { font-size: 11px; }

    .pc-layout { display: flex; gap: 12px; align-items: stretch; }
    #viewport { position: relative; flex: 1; height: 78vh; overflow: hidden; border: 1px solid #ccc; background: #f8fafc; border-radius: 6px; }
    #viewport.desenhando { cursor: crosshair; }
    #viewport.desenhando #panzoom-target { cursor: crosshair; }
    .pc-lado { width: 290px; flex: 0 0 290px; display: flex; flex-direction: column; gap: 10px; }
    .pc-lado .card-body { padding: 10px 12px; }
    .pc-lista { list-style: none; margin: 0; padding: 0; max-height: 40vh; overflow-y: auto; }
    .pc-lado { overflow-y: auto; }
    .pc-lista li { display: flex; align-items: center; gap: 8px; padding: 5px 6px; border-radius: 6px; cursor: pointer; font-size: 13px; }
    .pc-lista li:hover { background: #f1f5f9; }
    .pc-lista li.sel { background: #e0ecff; font-weight: 600; }
    .pc-lista li .amostra { width: 22px; flex: 0 0 22px; height: 0; border-top-style: solid; border-radius: 3px; }
    .pc-lista li small { color: #64748b; font-weight: normal; }

    /* camada do editor */
    #pc-svg { position: absolute; inset: 0; width: 100%; height: 100%; overflow: visible; z-index: 3; pointer-events: none; }
    #pc-svg .alvo { fill: none; stroke: transparent; stroke-width: 14; pointer-events: stroke; cursor: pointer; }
    #pc-svg g.sel .fio { filter: drop-shadow(0 0 3px #0d6efd); }
    #viewport.desenhando #pc-svg .alvo { pointer-events: none; }
    .pc-alca { position: absolute; z-index: 4; width: 14px; height: 14px; margin: -7px 0 0 -7px; border-radius: 50%;
               background: #fff; border: 2px solid #0d6efd; cursor: move; transform: scale(var(--pc-inv, 1)); touch-action: none; }
    .pc-alca.meio { width: 10px; height: 10px; margin: -5px 0 0 -5px; border-style: dashed; background: rgba(255,255,255,.8); cursor: copy; }
    .planta-ref { opacity: .55; pointer-events: none; }
    .planta-ref.oculta { display: none; }
    .pc-toast { position: fixed; right: 20px; bottom: 20px; z-index: 1090; padding: 10px 14px; border-radius: 6px; color: #fff; font-size: 14px; background: #198754; opacity: 0; transform: translateY(10px); transition: opacity .2s, transform .2s; pointer-events: none; }
    .pc-toast.show { opacity: 1; transform: none; }
    .pc-toast.err { background: #dc3545; }
</style>

<div data-foco-alvo>
<div class="pc-topo">
    <div>
        <h1 class="h4 mb-0"><i class="fas fa-route"></i> Infraestrutura: {{ $planta->name }}</h1>
        <small class="text-muted">{{ $planta->predio?->nome }} · eletrocalhas, canaletas e dutos</small>
    </div>
    <div class="d-flex gap-2">
        @include('partials.tela-cheia')
        <a href="{{ route('plantas.public', ['planta' => $planta]) }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-eye"></i> Ver planta</a>
        <a href="{{ route('plantas.index', ['predio' => $planta->predio_id]) }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
</div>

<div class="pc-barra">
    <strong class="small mr-1">Desenhar:</strong>
    @foreach($tipos as $chave => $t)
        <button type="button" class="btn btn-light btn-sm pc-tipo-btn" data-tipo="{{ $chave }}">
            <span class="amostra" style="border-top-width: {{ max(2, $t['largura']) }}px; border-top-color: {{ $t['cor'] }}; {{ $t['traco'] ? 'border-top-style: dashed;' : '' }}"></span>
            {{ $t['nome'] }}
        </button>
    @endforeach
    <span class="sep"></span>
    <label class="small mb-0 d-inline-flex align-items-center gap-1"><input type="checkbox" id="pc-ref" checked> Mostrar pontos e salas/ambientes</label>
    <span class="sep"></span>
    <button type="button" class="btn btn-light btn-sm" id="btnZoomIn">+</button>
    <button type="button" class="btn btn-light btn-sm" id="btnZoomOut">−</button>
    <button type="button" class="btn btn-light btn-sm" id="btnZoomReset">Zoom</button>
    <span class="pc-dica ml-auto" id="pc-dica"></span>
</div>

<div class="pc-layout">
    <div id="viewport" data-foco-altura>
        <div id="panzoom-target" style="position: relative; width: 100%; transform-origin: 0 0; cursor: grab; --escala-marcadores: {{ $planta->escala_marcadores ?? 1 }};">
            <div class="planta-moldura" style="{{ $recorte['moldura'] }}">
                <div class="planta-camada" id="pc-camada" style="{{ $recorte['camada'] }}">
                    <img id="svg-image" src="{{ route('plantas.show', ['predio' => $planta->predio_id, 'planta' => $planta]) }}"
                         style="width: 100%; height: {{ $planta->recorteValido() ? '100%' : 'auto' }}; display: block; user-select: none;" draggable="false" alt="{{ $planta->name }}">
                    <div class="planta-ref" id="pc-refs">
                        @include('plantas.partials.markers', ['markers' => $planta->markers])
                        @include('salas.partials.markers', ['salas' => $planta->salas])
                    </div>
                    <svg id="pc-svg" viewBox="0 0 100 100" preserveAspectRatio="none"></svg>
                    <div id="pc-alcas"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="pc-lado">
        <div class="card">
            <div class="card-body" id="pc-editar" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="small">Percurso selecionado</strong>
                    <button type="button" class="btn btn-link btn-sm p-0" id="pc-desselecionar">fechar</button>
                </div>
                <label class="small font-weight-bold mb-1" for="pc-tipo">Tipo</label>
                <select id="pc-tipo" class="form-select form-select-sm mb-2" autocomplete="off">
                    @foreach($tipos as $chave => $t)
                        <option value="{{ $chave }}">{{ $t['nome'] }}</option>
                    @endforeach
                </select>
                <label class="small font-weight-bold mb-1" for="pc-descricao">Descrição</label>
                <input type="text" id="pc-descricao" class="form-control form-control-sm mb-2" maxlength="255" placeholder="Ex.: eletrocalha 100mm sobre o forro" autocomplete="off">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary btn-sm" id="pc-salvar">Salvar</button>
                    <button type="button" class="btn btn-outline-danger btn-sm ml-auto" id="pc-excluir"><i class="fas fa-trash"></i> Excluir</button>
                </div>
                <div class="small text-muted mt-2">
                    Arraste os pontos para ajustar. Puxe um ponto tracejado (meio do trecho) para criar uma curva.
                    <kbd>Alt</kbd>+clique num ponto remove o ponto.
                </div>
            </div>
            <div class="card-body" id="pc-ajuda">
                <strong class="small d-block mb-1">Como desenhar</strong>
                <ol class="small pl-3 mb-0">
                    <li>Escolha o tipo na barra acima.</li>
                    <li>Clique na planta ponto a ponto (arrastar continua movendo a planta).</li>
                    <li><kbd>Duplo clique</kbd> ou <kbd>Enter</kbd> termina. <kbd>Backspace</kbd> desfaz o último ponto, <kbd>Esc</kbd> cancela.</li>
                    <li>Segure <kbd>Shift</kbd> para trechos retos (horizontal/vertical).</li>
                </ol>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <strong class="small d-block mb-1">Percursos nesta planta (<span id="pc-total">0</span>)</strong>
                <ul class="pc-lista" id="pc-lista"></ul>
                <div class="small text-muted" id="pc-vazio">Nenhum percurso desenhado ainda.</div>
            </div>
        </div>
    </div>
</div>
</div>
<div class="pc-toast" id="pc-toast"></div>
@endsection

@section('javascripts_bottom')
<script>
(function () {
    const TIPOS = @json($tipos);
    const URL_NOVO = @json(route('plantas.percursos.store', ['planta' => $planta]));
    const URL_PERCURSO = @json(url('/percursos'));
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const SVGNS = 'http://www.w3.org/2000/svg';

    const viewport = document.getElementById('viewport');
    const alvoPz = document.getElementById('panzoom-target');
    const camada = document.getElementById('pc-camada');
    const svg = document.getElementById('pc-svg');
    const alcas = document.getElementById('pc-alcas');
    const img = document.getElementById('svg-image');
    const dica = document.getElementById('pc-dica');

    let percursos = @json($planta->percursos->map->paraJs()->values());
    let desenho = null;        // { tipo, pontos: [[x,y]...] } enquanto desenha
    let mouse = null;          // posição atual do mouse em % (para o trecho provisório)
    let selecionado = null;    // id do percurso selecionado
    let panzoom = null;

    // ---------- util ----------
    async function api(method, url, body) {
        const r = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: body ? JSON.stringify(body) : undefined,
        });
        const d = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(d.message || 'Erro ao salvar');
        return d;
    }
    let toastTimer;
    function toast(texto, erro) {
        const t = document.getElementById('pc-toast');
        t.textContent = texto;
        t.className = 'pc-toast show' + (erro ? ' err' : '');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.className = 'pc-toast', 2500);
    }
    function pct(e) {
        const r = camada.getBoundingClientRect();
        return [(e.clientX - r.left) / r.width * 100, (e.clientY - r.top) / r.height * 100];
    }
    // Shift: trava o trecho na horizontal ou vertical (comparando em pixels da tela)
    function travar(p, anterior) {
        if (!anterior) return p;
        const r = camada.getBoundingClientRect();
        const dx = (p[0] - anterior[0]) * r.width, dy = (p[1] - anterior[1]) * r.height;
        return Math.abs(dx) >= Math.abs(dy) ? [p[0], anterior[1]] : [anterior[0], p[1]];
    }
    const pontosAttr = pts => pts.map(p => `${p[0].toFixed(3)},${p[1].toFixed(3)}`).join(' ');
    function el(tag, attrs, pai) {
        const e = document.createElementNS(SVGNS, tag);
        for (const k in attrs) if (attrs[k] !== null && attrs[k] !== undefined) e.setAttribute(k, attrs[k]);
        if (pai) pai.appendChild(e);
        return e;
    }

    // ---------- desenho ----------
    function linha(pai, pts, tipo, extra) {
        const e = TIPOS[tipo] || TIPOS.canaleta;
        const base = { points: pontosAttr(pts), fill: 'none', 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'vector-effect': 'non-scaling-stroke' };
        el('polyline', { ...base, stroke: 'rgba(0,0,0,.35)', 'stroke-width': e.largura + 1 }, pai);
        el('polyline', { ...base, class: 'fio', stroke: e.cor, 'stroke-width': e.largura, 'stroke-dasharray': e.traco, ...(extra || {}) }, pai);
    }

    function render() {
        svg.innerHTML = '';
        percursos.forEach(p => {
            const g = el('g', { class: 'percurso' + (p.id === selecionado ? ' sel' : ''), 'data-id': p.id }, svg);
            linha(g, p.pontos, p.tipo);
            el('polyline', { points: pontosAttr(p.pontos), class: 'alvo', 'vector-effect': 'non-scaling-stroke', 'data-id': p.id }, g);
        });
        if (desenho) {
            const pts = mouse ? [...desenho.pontos, mouse] : desenho.pontos;
            if (pts.length >= 2) linha(svg, pts, desenho.tipo, { opacity: .8 });
            desenho.pontos.forEach(p => el('circle', { cx: p[0], cy: p[1], r: .25, fill: '#0d6efd', 'vector-effect': 'non-scaling-stroke' }, svg));
        }
        renderAlcas();
        renderLista();
    }

    // Alças de edição (HTML, para ficarem redondas e do mesmo tamanho com qualquer zoom)
    function renderAlcas() {
        alcas.innerHTML = '';
        const p = percursos.find(x => x.id === selecionado);
        if (!p || desenho) return;
        p.pontos.forEach((pt, i) => {
            const a = document.createElement('div');
            a.className = 'pc-alca';
            a.style.left = pt[0] + '%';
            a.style.top = pt[1] + '%';
            a.title = 'Arraste para mover · Alt+clique remove';
            a.addEventListener('pointerdown', e => iniciarArraste(e, p, i, false));
            alcas.appendChild(a);
            if (i < p.pontos.length - 1) {
                const q = p.pontos[i + 1];
                const m = document.createElement('div');
                m.className = 'pc-alca meio';
                m.style.left = (pt[0] + q[0]) / 2 + '%';
                m.style.top = (pt[1] + q[1]) / 2 + '%';
                m.title = 'Puxe para criar um ponto';
                m.addEventListener('pointerdown', e => iniciarArraste(e, p, i + 1, true));
                alcas.appendChild(m);
            }
        });
    }

    function iniciarArraste(e, p, indice, novo) {
        e.stopPropagation();
        e.preventDefault();
        if (e.altKey && !novo) {
            if (p.pontos.length <= 2) { toast('O percurso precisa de pelo menos 2 pontos.', true); return; }
            p.pontos.splice(indice, 1);
            render();
            salvarPontos(p);
            return;
        }
        if (novo) p.pontos.splice(indice, 0, pct(e));
        const anterior = p.pontos[indice - 1];
        let moveu = novo;
        const mover = ev => {
            moveu = true;
            let pos = pct(ev);
            if (ev.shiftKey) pos = travar(pos, anterior || p.pontos[indice + 1]);
            p.pontos[indice] = pos;
            render();
        };
        const soltar = () => {
            window.removeEventListener('pointermove', mover);
            window.removeEventListener('pointerup', soltar);
            if (moveu) salvarPontos(p);
        };
        window.addEventListener('pointermove', mover);
        window.addEventListener('pointerup', soltar);
    }

    async function salvarPontos(p) {
        try {
            await api('PUT', `${URL_PERCURSO}/${p.id}`, { pontos: p.pontos });
        } catch (e) { toast(e.message, true); }
    }

    function renderLista() {
        const ul = document.getElementById('pc-lista');
        ul.innerHTML = '';
        document.getElementById('pc-total').textContent = percursos.length;
        document.getElementById('pc-vazio').style.display = percursos.length ? 'none' : '';
        percursos.forEach(p => {
            const t = TIPOS[p.tipo] || TIPOS.canaleta;
            const li = document.createElement('li');
            li.className = p.id === selecionado ? 'sel' : '';
            const amostra = document.createElement('span');
            amostra.className = 'amostra';
            amostra.style.borderTopWidth = Math.max(2, t.largura) + 'px';
            amostra.style.borderTopColor = t.cor;
            if (t.traco) amostra.style.borderTopStyle = 'dashed';
            const txt = document.createElement('span');
            txt.textContent = t.nome;
            if (p.descricao) {
                const s = document.createElement('small');
                s.textContent = ' — ' + p.descricao;
                txt.appendChild(s);
            }
            li.append(amostra, txt);
            li.addEventListener('click', () => selecionar(p.id));
            ul.appendChild(li);
        });
    }

    // ---------- modos ----------
    function atualizarDica() {
        viewport.classList.toggle('desenhando', !!desenho);
        document.querySelectorAll('.pc-tipo-btn').forEach(b => b.classList.toggle('ativo', !!desenho && b.dataset.tipo === desenho.tipo));
        dica.innerHTML = desenho
            ? `Desenhando <strong>${TIPOS[desenho.tipo].nome}</strong>: clique para adicionar pontos · <kbd>Duplo clique</kbd>/<kbd>Enter</kbd> termina · <kbd>Backspace</kbd> desfaz · <kbd>Esc</kbd> cancela · <kbd>Shift</kbd> trecho reto`
            : 'Escolha um tipo para desenhar, ou clique num percurso para editar.';
    }

    function comecar(tipo) {
        selecionar(null);
        desenho = { tipo, pontos: [] };
        mouse = null;
        atualizarDica();
        render();
    }
    function cancelar() {
        desenho = null;
        mouse = null;
        atualizarDica();
        render();
    }
    async function terminar() {
        if (!desenho) return;
        // remove pontos repetidos seguidos (duplo clique adiciona o mesmo ponto duas vezes)
        const pts = desenho.pontos.filter((p, i, a) => i === 0 || Math.hypot(p[0] - a[i - 1][0], p[1] - a[i - 1][1]) > 0.05);
        if (pts.length < 2) { toast('Clique pelo menos 2 pontos.', true); return; }
        const tipo = desenho.tipo;
        cancelar();
        try {
            const d = await api('POST', URL_NOVO, { tipo, pontos: pts });
            percursos.push(d.percurso);
            selecionar(d.percurso.id);
            toast(`Percurso salvo (${TIPOS[tipo].nome}).`);
        } catch (e) { toast(e.message, true); }
    }

    function selecionar(id) {
        selecionado = id;
        const p = percursos.find(x => x.id === id);
        document.getElementById('pc-editar').style.display = p ? '' : 'none';
        document.getElementById('pc-ajuda').style.display = p ? 'none' : '';
        if (p) {
            document.getElementById('pc-tipo').value = p.tipo;
            document.getElementById('pc-descricao').value = p.descricao || '';
        }
        render();
    }

    // ---------- eventos ----------
    document.querySelectorAll('.pc-tipo-btn').forEach(b => b.addEventListener('click', () => {
        if (desenho && desenho.tipo === b.dataset.tipo) cancelar(); else comecar(b.dataset.tipo);
    }));
    document.getElementById('pc-ref').addEventListener('change', e => document.getElementById('pc-refs').classList.toggle('oculta', !e.target.checked));
    document.getElementById('pc-desselecionar').addEventListener('click', () => selecionar(null));

    document.getElementById('pc-salvar').addEventListener('click', async () => {
        const p = percursos.find(x => x.id === selecionado);
        if (!p) return;
        try {
            const d = await api('PUT', `${URL_PERCURSO}/${p.id}`, {
                tipo: document.getElementById('pc-tipo').value,
                descricao: document.getElementById('pc-descricao').value || null,
            });
            Object.assign(p, d.percurso);
            render();
            toast('Percurso atualizado.');
        } catch (e) { toast(e.message, true); }
    });
    document.getElementById('pc-excluir').addEventListener('click', async () => {
        const p = percursos.find(x => x.id === selecionado);
        if (!p || !confirm(`Excluir este percurso (${TIPOS[p.tipo].nome})?`)) return;
        try {
            await api('DELETE', `${URL_PERCURSO}/${p.id}`);
            percursos = percursos.filter(x => x.id !== p.id);
            selecionar(null);
            toast('Percurso excluído.');
        } catch (e) { toast(e.message, true); }
    });

    // Clique (sem arrastar) na planta: adiciona ponto ou seleciona percurso
    let inicio = null;
    // Fase de captura: o Panzoom trata o pointerdown no alvo e não deixa o evento subir
    viewport.addEventListener('pointerdown', e => { inicio = { x: e.clientX, y: e.clientY, alvo: e.target }; }, true);
    window.addEventListener('pointerup', e => {
        if (!inicio || Math.hypot(e.clientX - inicio.x, e.clientY - inicio.y) > 5) { inicio = null; return; }
        const alvo = inicio.alvo;
        inicio = null;
        if (alvo.closest && alvo.closest('.pc-alca')) return;
        if (desenho) {
            let p = pct(e);
            if (e.shiftKey) p = travar(p, desenho.pontos[desenho.pontos.length - 1]);
            desenho.pontos.push(p);
            render();
            return;
        }
        const id = alvo.dataset && alvo.dataset.id && alvo.classList.contains('alvo') ? Number(alvo.dataset.id) : null;
        selecionar(id);
    }, true);
    viewport.addEventListener('dblclick', e => { if (desenho) { e.preventDefault(); terminar(); } }, true);
    viewport.addEventListener('pointermove', e => {
        if (!desenho || !desenho.pontos.length) return;
        mouse = pct(e);
        if (e.shiftKey) mouse = travar(mouse, desenho.pontos[desenho.pontos.length - 1]);
        render();
    }, true);
    viewport.addEventListener('mouseleave', () => { if (desenho) { mouse = null; render(); } });

    document.addEventListener('keydown', e => {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) return;
        if (desenho) {
            if (e.key === 'Escape') cancelar();
            else if (e.key === 'Enter') { e.preventDefault(); terminar(); }
            else if (e.key === 'Backspace') { e.preventDefault(); desenho.pontos.pop(); render(); }
        } else if (e.key === 'Escape') {
            selecionar(null);
        } else if (e.key === 'Delete' && selecionado) {
            document.getElementById('pc-excluir').click();
        }
    });

    // ---------- zoom ----------
    function initPanzoom() {
        panzoom = Panzoom(alvoPz, { maxScale: 10, minScale: 0.8, contain: 'outside' });
        setTimeout(() => panzoom.reset(), 50);
        viewport.addEventListener('wheel', panzoom.zoomWithWheel);
        document.getElementById('btnZoomIn').addEventListener('click', panzoom.zoomIn);
        document.getElementById('btnZoomOut').addEventListener('click', panzoom.zoomOut);
        document.getElementById('btnZoomReset').addEventListener('click', panzoom.reset);
        // mantém as alças do mesmo tamanho na tela
        alvoPz.addEventListener('panzoomchange', e => alvoPz.style.setProperty('--pc-inv', 1 / e.detail.scale));
    }
    if (img.complete) initPanzoom(); else img.addEventListener('load', initPanzoom);

    atualizarDica();
    render();
})();
</script>
@endsection

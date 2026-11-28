@extends('main')

@section('content')
<!-- Inclusão do Panzoom via CDN -->
<script src="https://unpkg.com/@panzoom/panzoom@4.5.1/dist/panzoom.min.js"></script>

@php
    $salasParaSugestao = $salasPredio->map(fn ($s) => [
        'id' => $s->id,
        'x' => $s->x !== null ? (float) $s->x : null,
        'y' => $s->y !== null ? (float) $s->y : null,
        'planta' => $s->planta_id,
    ])->values();
    $recorte = $planta->estilosRecorte();
    $temRecorte = (bool) $planta->recorteValido();
    $escala = (float) ($planta->escala_marcadores ?? 1);
@endphp

<style>
    .mk-toolbar { display: flex; flex-wrap: wrap; gap: 8px 14px; align-items: center; margin: 10px 0; font-size: 13px; }
    .mk-toolbar .grupo { display: inline-flex; gap: 5px; align-items: center; background: #f8fafc; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; }
    .mk-toolbar button { padding: 3px 10px; cursor: pointer; border: 1px solid #94a3b8; background: #fff; border-radius: 4px; font-size: 13px; }
    .mk-toolbar button:disabled { opacity: .45; cursor: default; }
    .mk-toolbar .primario { background: #0d6efd; color: #fff; border-color: #0d6efd; }
    .mk-toolbar .perigo { background: #fff; color: #dc3545; border-color: #dc3545; }
    .mk-ajuda { font-size: 11px; color: #64748b; }

    .mk-layout { display: flex; gap: 12px; align-items: stretch; }
    #viewport { position: relative; flex: 1; min-width: 0; height: 80vh; overflow: hidden; border: 1px solid #ccc; background-color: #f8fafc; border-radius: 6px; }

    /* Lista lateral */
    .mk-lista { width: 270px; flex: 0 0 270px; height: 80vh; display: flex; flex-direction: column; border: 1px solid #ccc; border-radius: 6px; background: #fff; }
    .mk-lista header { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
    .mk-lista header strong { font-size: 13px; }
    .mk-lista header small { display: block; color: #64748b; font-size: 11px; margin: 2px 0 6px; }
    .mk-lista input[type=search] { width: 100%; padding: 4px 8px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 4px; }
    .mk-lista ul { list-style: none; margin: 0; padding: 4px; overflow-y: auto; flex: 1; }
    .mk-lista li { padding: 5px 8px; margin: 2px 0; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 12px; cursor: grab; background: #f8fafc; display: flex; align-items: center; gap: 6px; user-select: none; }
    .mk-lista li:hover { border-color: #0d6efd; background: #eef4ff; }
    .mk-lista li .tri { flex: 0 0 auto; width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-bottom: 9px solid var(--cor, #ef4444); }
    .mk-lista li small { color: #64748b; margin-left: auto; white-space: nowrap; }
    .mk-lista .vazio { padding: 12px; color: #64748b; font-size: 12px; text-align: center; }
    .mk-lista li.grupo { cursor: default; background: none; border: 0; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .03em; padding: 8px 4px 2px; }
    .mk-lista li.grupo:hover { background: none; }
    #viewport.is-drop { outline: 3px dashed #0d6efd; outline-offset: -3px; }

    /* Seleção, rótulo livre e destaque */
    .mk-page .marker-ponto.is-selecionado .marker-icone { filter: drop-shadow(0 0 2px #0d6efd) drop-shadow(0 0 4px #0d6efd); }
    .mk-page .marker-ponto.is-selecionado .marker-label { outline: 1px dashed #0d6efd; outline-offset: 2px; }
    .mk-page .marker-livre .marker-label { pointer-events: auto !important; cursor: move; }

    /* Recorte */
    #recorte-overlay { position: absolute; inset: 0; z-index: 50; cursor: crosshair; display: none; }
    #recorte-rect { position: absolute; border: 2px solid #0d6efd; box-shadow: 0 0 0 99999px rgba(15, 23, 42, .45); pointer-events: none; }

    .mk-toast { position: fixed; right: 20px; bottom: 20px; z-index: 2000; padding: 9px 14px; border-radius: 6px; color: #fff; font-size: 13px; box-shadow: 0 4px 12px rgba(0,0,0,.2); opacity: 0; transform: translateY(8px); transition: opacity .2s, transform .2s; pointer-events: none; }
    .mk-toast.show { opacity: 1; transform: none; }
    .mk-toast.ok { background: #198754; } .mk-toast.err { background: #dc3545; } .mk-toast.info { background: #334155; }
</style>

<div class="mk-page" data-foco-alvo>

<div class="mk-toolbar">
    <a href="{{ route('plantas.index', ['predio' => $planta->predio_id]) }}" style="text-decoration: none; color: #000; background-color: #f0f0f0; padding: 5px 12px; border-radius: 4px;">
        &larr; Voltar
    </a>
    @include('partials.tela-cheia')

    <div class="grupo">
        <button type="button" id="btnZoomIn" title="Aproximar">+</button>
        <button type="button" id="btnZoomOut" title="Afastar">−</button>
        <button type="button" id="btnZoomReset">Redefinir zoom</button>
    </div>

    <div class="grupo" title="Tamanho de todas as marcações desta planta (multiplica o tamanho de cada ponto)">
        <label for="escala" style="margin: 0;">Tamanho geral</label>
        <input type="range" id="escala" min="0.3" max="3" step="0.1" value="{{ $escala }}">
        <span id="escalaValor" style="min-width: 34px;">{{ number_format($escala, 1, ',', '') }}×</span>
    </div>

    <div class="grupo" id="grupoRecorte">
        <button type="button" id="btnRecortar">✂ {{ $temRecorte ? 'Ajustar recorte' : 'Recortar bordas' }}</button>
        <button type="button" id="btnSalvarRecorte" class="primario" style="display: none;">Salvar recorte</button>
        <button type="button" id="btnSemRecorte" class="perigo" style="display: none;">Sem recorte</button>
        <button type="button" id="btnCancelarRecorte" style="display: none;">Cancelar</button>
    </div>

    <div class="grupo">
        <button type="button" id="btnDesfazer" disabled title="Ctrl+Z">↶ Desfazer</button>
    </div>

    <span class="mk-ajuda" id="ajuda">
        Clique num ponto para editar · arraste para mover · setas ajustam 1px (Shift: 10px) · Esc solta a seleção
    </span>
</div>

<div class="mk-layout">
    <!-- Viewport fixa para conter o zoom -->
    <div id="viewport" data-foco-altura>

        <!-- Target do Panzoom -->
        <div id="panzoom-target" style="position: relative; width: 100%; transform-origin: 0 0; cursor: grab; --escala-marcadores: {{ $escala }};">
            <div class="planta-moldura" id="moldura" style="{{ $recorte['moldura'] }}">
                <div class="planta-camada" id="camada" style="{{ $recorte['camada'] }}">
                    <!-- Imagem SVG da Planta Baixa -->
                    <img id="svg-image" src="{{ route('plantas.show', ['predio' => $planta->predio_id, 'planta' => $planta]) }}" style="width: 100%; height: {{ $temRecorte ? '100%' : 'auto' }}; display: block; user-select: none;" draggable="false" alt="Planta Baixa">

                    <!-- Renderização dos Marcadores Salvos -->
                    @include('plantas.partials.markers')
                    <div style="pointer-events: none;">
                        @include('salas.partials.markers', ['salas' => $salasMarkerd])
                    </div>

                    <!-- Desenho do recorte -->
                    <div id="recorte-overlay"><div id="recorte-rect"></div></div>
                </div>
            </div>
        </div>

        <!-- Formulário Pop-up Nativo (Edição / Inserção) -->
        <div id="popoverForm" style="display: none; position: absolute; background: #fff; border: 1px solid #000; padding: 12px; z-index: 1000; min-width: 280px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 6px;">

            <strong id="formTitle" style="display: block; font-size: 13px; margin-bottom: 8px;">Selecione o Ponto:</strong>

            <!-- Formulário Unificado: Salvar/Atualizar -->
            <form action="{{ route('plantas.mark.update', ['planta' => $planta]) }}" method="POST" id="mainForm">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="PUT">

                <input type="hidden" name="x" id="inputX">
                <input type="hidden" name="y" id="inputY">
                <input type="hidden" name="label_dx" id="inputDx">
                <input type="hidden" name="label_dy" id="inputDy">
                <input type="hidden" name="planta_id" value="{{ $planta->id }}">

                <!-- Campo Ponto para Modo Inserção -->
                <div id="divSelectPonto" style="margin-bottom: 8px;">
                    <label style="display: block; font-size: 11px; margin-bottom: 2px; font-weight: bold;">Ponto:</label>
                    <select name="patch_panel_sala_id" id="patch_panel_sala_id" style="width: 100%; padding: 5px; font-size: 12px;">
                        <option value="">-- Selecione --</option>
                        @foreach($pontosSemMarcacao as $ponto)
                        @php $nomePonto = optional(optional($ponto->patchPanel)->rack)->nome . '-' . optional($ponto->patchPanel)->nome . '-' . $ponto->porta; @endphp
                        <option value="{{ $ponto->id }}"
                                data-nome="{{ $nomePonto }}"
                                data-tipo="{{ $ponto->tipo_porta_id }}"
                                data-comentario="{{ $ponto->comentario }}"
                                data-tamanho="{{ $ponto->tamanho }}"
                                data-fontsize="{{ $ponto->fontsize ?? 12 }}"
                                data-labelposition="{{ $ponto->label_position ?? 'right' }}">
                            {{ $nomePonto }}
                            @if($ponto->sala) ({{ $ponto->sala->nome }}) @endif
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Campo de Leitura Apenas no Modo Edição -->
                <div id="divReadonlyPonto" style="display: none; margin-bottom: 8px;">
                    <label style="display: block; font-size: 11px; margin-bottom: 2px; font-weight: bold;">Ponto Selecionado:</label>
                    <input type="text" id="inputPontoNome" readonly style="width: 100%; padding: 5px; font-size: 12px; background: #e9ecef; border: 1px solid #ced4da; border-radius: 3px; box-sizing: border-box;">
                </div>

                <!-- Sala: só aparece para porta da lista que ainda não tem sala -->
                <div id="divSalaLivre" style="display: none; margin-bottom: 8px;">
                    <label for="salaLivre" style="display: block; font-size: 11px; margin-bottom: 2px; font-weight: bold;">Sala/Ambiente desta porta *</label>
                    <select id="salaLivre" style="width: 100%; padding: 5px; font-size: 12px;">
                        @foreach($salasPredio as $sala)
                            <option value="{{ $sala->id }}">{{ $sala->nome }}</option>
                        @endforeach
                    </select>
                    <small style="font-size: 10px; color: #64748b;">Sugerida: a sala/ambiente marcada mais próxima.</small>
                </div>

                <!-- Tipo de Porta -->
                <div style="margin-bottom: 8px;">
                    <label for="tipo_porta_id" style="display: block; font-size: 11px; margin-bottom: 2px;">Tipo de Porta (Opcional):</label>
                    <select name="tipo_porta_id" id="tipo_porta_id" style="width: 100%; padding: 5px; font-size: 12px;">
                        <option value="">-- Não informar tipo --</option>
                        @if(isset($tipoPortas))
                            @foreach($tipoPortas as $tipoPorta)
                                <option value="{{ $tipoPorta->id }}">{{ $tipoPorta->nome }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Comentário -->
                <div style="margin-bottom: 8px;">
                    <label for="comentario" style="display: block; font-size: 11px; margin-bottom: 2px;">Comentário (Opcional):</label>
                    <input type="text" name="comentario" id="comentario" placeholder="Ex: Atrás da mesa" style="width: 100%; padding: 5px; box-sizing: border-box; font-size: 12px;">
                </div>

                <!-- Comprimento Cabeamento Horizontal (tamanho) -->
                <div style="margin-bottom: 8px;">
                    <label for="tamanho" style="display: block; font-size: 11px; margin-bottom: 2px;">Comprimento Cabeamento Horizontal (m):</label>
                    <div style="display: flex; align-items: center;">
                        <input type="number" step="0.01" name="tamanho" id="tamanho" placeholder="0.00" style="width: 100%; padding: 5px; box-sizing: border-box; font-size: 12px; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                        <span style="background: #e9ecef; border: 1px solid #767676; border-left: none; padding: 4px 8px; font-size: 12px; border-top-right-radius: 3px; border-bottom-right-radius: 3px;">m</span>
                    </div>
                </div>

                <!-- Tamanho da Fonte -->
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 11px; margin-bottom: 2px;">Tamanho da Fonte (px, antes do tamanho geral):</label>
                    <input type="number" name="fontsize" id="inputFontsize" value="12" min="2" max="40" required style="width: 100%; padding: 5px; box-sizing: border-box; font-size: 12px;">
                </div>

                <!-- Posição da Legenda -->
                <div style="margin-bottom: 12px;">
                    <label for="label_position" style="display: block; font-size: 11px; margin-bottom: 2px;">Posição da Legenda:</label>
                    <select name="label_position" id="label_position" required style="width: 100%; padding: 5px; font-size: 12px;">
                        <option value="right">Direita</option>
                        <option value="left">Esquerda</option>
                        <option value="top">Acima</option>
                        <option value="bottom">Abaixo</option>
                        <option value="livre">Livre, com linha guia (arraste o texto)</option>
                    </select>
                </div>

                <!-- Ações do Formulário principal -->
                <div style="display: flex; justify-content: space-between; gap: 5px;">
                    <button type="submit" id="btnSalvar" style="cursor: pointer; padding: 5px 10px; font-size: 12px; background: #0d6efd; color: #fff; border: none; border-radius: 3px;">Salvar Ponto</button>
                    <button type="button" onclick="closeForm()" style="cursor: pointer; padding: 5px 10px; font-size: 12px;">Cancelar</button>
                </div>
            </form>

            <!-- Formulário Separado: Apenas para Ação de Remoção -->
            <form id="deleteForm" action="" method="POST" style="display: none; margin-top: 8px; border-top: 1px solid #eee; padding-top: 8px;">
                @csrf
                @method('DELETE')

                <button type="submit" onclick="return confirm('Desvincular esta marcação da planta?');" style="width: 100%; background: #dc3545; color: #fff; border: none; padding: 6px; cursor: pointer; border-radius: 3px; font-weight: bold; font-size: 12px;">
                    Remover Marcação
                </button>
            </form>

        </div>
    </div>

    <!-- Lista lateral de pontos sem marcação -->
    @php $totalLista = $pontosSemMarcacao->count() + count($portasSemSala); @endphp
    <aside class="mk-lista" data-foco-altura>
        <header>
            <strong>Pontos sem marcação (<span id="qtdeLista">{{ $totalLista }}</span>)</strong>
            <small>Arraste um ponto para o lugar dele na planta.</small>
            <input type="search" id="buscaLista" placeholder="Buscar por ponto ou sala/ambiente…">
        </header>
        <ul id="lista">
            @if($pontosSemMarcacao->isNotEmpty())
                <li class="grupo" data-grupo="vinculo">Com sala/ambiente ({{ $pontosSemMarcacao->count() }})</li>
            @endif
            @foreach($pontosSemMarcacao as $ponto)
                @php $nomePonto = optional(optional($ponto->patchPanel)->rack)->nome . '-' . optional($ponto->patchPanel)->nome . '-' . $ponto->porta; @endphp
                <li draggable="true"
                    data-grupo="vinculo"
                    data-id="{{ $ponto->id }}"
                    data-nome="{{ $nomePonto }}"
                    data-fontsize="{{ $ponto->fontsize ?? 12 }}"
                    data-labelposition="{{ $ponto->label_position ?? 'right' }}"
                    data-busca="{{ Str::lower($nomePonto . ' ' . ($ponto->sala->nome ?? '')) }}"
                    style="--cor: {{ optional($ponto->tipoPorta)->cor ?? '#ef4444' }};">
                    <span class="tri"></span>
                    <strong>{{ $nomePonto }}</strong>
                    <small>{{ $ponto->sala->nome ?? '' }}</small>
                </li>
            @endforeach

            @if(count($portasSemSala))
                <li class="grupo" data-grupo="livre">Sem sala/ambiente — escolha ao soltar ({{ count($portasSemSala) }})</li>
            @endif
            @foreach($portasSemSala as $porta)
                <li draggable="true"
                    data-grupo="livre"
                    data-livre="1"
                    data-pp="{{ $porta['pp'] }}"
                    data-porta="{{ $porta['porta'] }}"
                    data-nome="{{ $porta['nome'] }}"
                    data-fontsize="12"
                    data-labelposition="right"
                    data-busca="{{ Str::lower($porta['nome']) }}"
                    style="--cor: #94a3b8;">
                    <span class="tri"></span>
                    <strong>{{ $porta['nome'] }}</strong>
                    <small>sem sala/ambiente</small>
                </li>
            @endforeach
        </ul>
        <div class="vazio" id="listaVazia" style="{{ $totalLista ? 'display: none;' : '' }}">
            {{ $totalLista ? 'Nenhum ponto encontrado.' : 'Todas as portas dos patch panels do prédio já estão marcadas.' }}
        </div>
    </aside>
</div>

</div>

<div class="mk-toast" id="mkToast"></div>

<script>
    const plantaMarkUrl = @json(route('plantas.mark.update', ['planta' => $planta]));
    const plantaConfigUrl = @json(route('plantas.config', ['planta' => $planta]));
    const plantaUnmarkUrlTemplate = @json(route('plantas.unmark', ['patch_panel_sala_id' => '__ID__']));
    const PLANTA_ID = @json($planta->id);
    const RECORTE_ATUAL = @json($planta->recorteValido());
    const ESTILOS_RECORTE = @json($recorte);
    const CHAVE_ZOOM = 'mk-zoom-' + PLANTA_ID;
    const csrfToken = document.querySelector('#mainForm input[name="_token"]').value;

    const viewport = document.getElementById('viewport');
    const panzoomTarget = document.getElementById('panzoom-target');
    const svgImage = document.getElementById('svg-image');
    const popoverForm = document.getElementById('popoverForm');
    const deleteForm = document.getElementById('deleteForm');
    const mainForm = document.getElementById('mainForm');
    const selectPonto = document.getElementById('patch_panel_sala_id');
    const divSelectPonto = document.getElementById('divSelectPonto');
    const divReadonlyPonto = document.getElementById('divReadonlyPonto');

    let panzoom;
    let justDragged = false;
    let selecionado = null;      // marcador selecionado (teclado / desfazer)
    let modoRecorte = false;
    let formContexto = null;     // dados do ponto aberto no formulário

    // ================== Utilidades ==================
    function toast(msg, tipo) {
        const t = document.getElementById('mkToast');
        t.textContent = msg;
        t.className = 'mk-toast show ' + (tipo || 'ok');
        clearTimeout(toast._t);
        toast._t = setTimeout(() => t.classList.remove('show'), 2400);
    }

    function escalaAtual() {
        return parseFloat(getComputedStyle(panzoomTarget).getPropertyValue('--escala-marcadores')) || 1;
    }

    // Converte um ponto da tela (clientX/Y) em % da planta inteira
    function telaParaPct(clientX, clientY) {
        const r = svgImage.getBoundingClientRect();
        return {
            x: Math.max(0, Math.min(100, (clientX - r.left) / r.width * 100)),
            y: Math.max(0, Math.min(100, (clientY - r.top) / r.height * 100)),
        };
    }

    // Centro do ícone de um marcador, em % da planta
    function centroIconePct(marker) {
        const r = (marker.querySelector('.marker-icone') || marker).getBoundingClientRect();
        return telaParaPct(r.left + r.width / 2, r.top + r.height / 2);
    }

    // Largura do texto do rótulo (px sem zoom), para calcular a caixa ícone + rótulo
    const canvasMedida = document.createElement('canvas').getContext('2d');
    function larguraTexto(texto, fsPx) {
        const familia = getComputedStyle(panzoomTarget).fontFamily || 'sans-serif';
        canvasMedida.font = `700 ${fsPx}px ${familia}`;
        return canvasMedida.measureText(texto).width;
    }

    /**
     * Nos modos direita/esquerda/acima/abaixo, o x/y salvo é o CENTRO da caixa ícone + rótulo.
     * Dado onde o ÍCONE deve ficar, calcula esse centro (para o ícone cair exatamente no lugar).
     * No modo livre, o x/y é o próprio ícone.
     */
    function xyParaIcone(iconePct, nome, fontsize, pos) {
        if (pos === 'livre') return iconePct;
        const k = escalaAtual();
        const fs = fontsize * k;
        const espaco = Math.max(2, Math.round(fontsize * 0.3)) * k;
        const desloc = (espaco + larguraTexto(nome, fs)) / 2; // px sem zoom
        const w = svgImage.offsetWidth, h = svgImage.offsetHeight;
        const p = { ...iconePct };
        if (pos === 'right') p.x += desloc / w * 100;
        else if (pos === 'left') p.x -= desloc / w * 100;
        else if (pos === 'top') p.y -= desloc / h * 100;
        else if (pos === 'bottom') p.y += desloc / h * 100;
        return p;
    }

    // Deslocamento atual do rótulo em relação ao ícone, em "em" (para o modo livre)
    function deslocRotulo(marker) {
        const ic = marker.querySelector('.marker-icone').getBoundingClientRect();
        const lb = marker.querySelector('.marker-label').getBoundingClientRect();
        return {
            dx: ((lb.left + lb.width / 2) - (ic.left + ic.width / 2)) / ic.width,
            dy: ((lb.top + lb.height / 2) - (ic.top + ic.height / 2)) / ic.width,
        };
    }

    // Mensagem de erro do servidor (validação do Laravel), para mostrar na tela
    async function mensagemErro(resposta, padrao) {
        const j = await resposta.json().catch(() => ({}));
        if (j.errors) return Object.values(j.errors).flat().join(' ');
        return j.message || `${padrao} (erro ${resposta.status})`;
    }

    // ================== Salvar posição (AJAX) ==================
    async function salvarPosicao(id, dados) {
        const corpo = {
            _method: 'PUT',
            planta_id: PLANTA_ID,
            patch_panel_sala_id: id,
            x: Number(dados.x).toFixed(2),
            y: Number(dados.y).toFixed(2),
        };
        if (dados.dx !== undefined) {
            corpo.label_dx = Number(dados.dx).toFixed(2);
            corpo.label_dy = Number(dados.dy).toFixed(2);
        }
        if (dados.fontsize) corpo.fontsize = dados.fontsize;
        if (dados.label_position) corpo.label_position = dados.label_position;

        const r = await fetch(plantaMarkUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(corpo),
        });
        if (!r.ok) {
            const msg = await mensagemErro(r, 'Não foi possível salvar a posição.');
            toast(msg, 'err');
            throw new Error(msg);
        }
        return r.json();
    }

    // ================== Estado do marcador (para mover/desfazer) ==================
    function estadoDe(marker) {
        const e = { id: marker.dataset.id, x: parseFloat(marker.style.left), y: parseFloat(marker.style.top) };
        if (marker.classList.contains('marker-livre')) {
            e.dx = parseFloat(marker.dataset.dx);
            e.dy = parseFloat(marker.dataset.dy);
        }
        return e;
    }

    function aplicarEstado(marker, e) {
        marker.style.left = e.x.toFixed(2) + '%';
        marker.style.top = e.y.toFixed(2) + '%';
        if (e.dx !== undefined) {
            marker.dataset.dx = e.dx;
            marker.dataset.dy = e.dy;
            const lb = marker.querySelector('.marker-label');
            lb.style.left = `calc(var(--fs) * ${e.dx.toFixed(2)})`;
            lb.style.top = `calc(var(--fs) * ${e.dy.toFixed(2)})`;
            window.atualizarLinhasGuia(marker.parentNode);
        }
    }

    // ================== Desfazer (Ctrl+Z) ==================
    const pilhaDesfazer = [];
    const btnDesfazer = document.getElementById('btnDesfazer');
    function empilhar(e) {
        pilhaDesfazer.push(e);
        if (pilhaDesfazer.length > 50) pilhaDesfazer.shift();
        btnDesfazer.disabled = false;
    }
    function desfazer() {
        const e = pilhaDesfazer.pop();
        btnDesfazer.disabled = pilhaDesfazer.length === 0;
        if (!e) return;
        const marker = panzoomTarget.querySelector(`.marker-ponto[data-id="${e.id}"]`);
        if (!marker) return;
        aplicarEstado(marker, e);
        selecionar(marker);
        salvarPosicao(e.id, e).then(() => toast('Movimento desfeito', 'info')).catch(() => {});
    }
    btnDesfazer.addEventListener('click', desfazer);

    // ================== Seleção + ajuste fino pelo teclado ==================
    function selecionar(marker) {
        if (selecionado) selecionado.classList.remove('is-selecionado');
        selecionado = marker;
        if (marker) marker.classList.add('is-selecionado');
    }

    let timerTeclado = null;
    window.addEventListener('keydown', function (e) {
        const emCampo = e.target.closest('input, select, textarea, [contenteditable]');

        if ((e.ctrlKey || e.metaKey) && !e.shiftKey && e.key.toLowerCase() === 'z' && !emCampo) {
            e.preventDefault();
            desfazer();
            return;
        }
        if (e.key === 'Escape') {
            if (modoRecorte) { sairRecorte(); return; }
            closeForm();
            selecionar(null);
            return;
        }
        if (!selecionado || emCampo || modoRecorte) return;

        const passos = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] };
        if (!passos[e.key]) return;
        e.preventDefault();

        if (!timerTeclado) empilhar(estadoDe(selecionado)); // um "desfazer" por sequência de setas

        const px = e.shiftKey ? 10 : 1; // pixels na tela, no zoom atual
        const r = svgImage.getBoundingClientRect();
        const est = estadoDe(selecionado);
        est.x = Math.max(0, Math.min(100, est.x + passos[e.key][0] * px / r.width * 100));
        est.y = Math.max(0, Math.min(100, est.y + passos[e.key][1] * px / r.height * 100));
        aplicarEstado(selecionado, est);

        clearTimeout(timerTeclado);
        const alvo = selecionado;
        timerTeclado = setTimeout(() => {
            timerTeclado = null;
            salvarPosicao(alvo.dataset.id, estadoDe(alvo)).catch(() => {});
        }, 500);
    });

    // ================== Panzoom (com zoom preservado entre recarregamentos) ==================
    function salvarZoom() {
        try {
            const p = panzoom.getPan();
            sessionStorage.setItem(CHAVE_ZOOM, JSON.stringify({ s: panzoom.getScale(), x: p.x, y: p.y }));
        } catch (e) {}
    }
    function restaurarZoom() {
        try {
            const z = JSON.parse(sessionStorage.getItem(CHAVE_ZOOM) || 'null');
            if (z && z.s) {
                panzoom.zoom(z.s, { animate: false });
                setTimeout(() => panzoom.pan(z.x, z.y, { animate: false }), 0);
                return true;
            }
        } catch (e) {}
        return false;
    }

    function initPanzoom() {
        panzoom = Panzoom(panzoomTarget, {
            maxScale: 12,
            minScale: 0.8,
            contain: 'outside'
        });

        setTimeout(() => { if (!restaurarZoom()) panzoom.reset(); }, 50);
        panzoomTarget.addEventListener('panzoomend', salvarZoom);
        panzoomTarget.addEventListener('panzoomzoom', () => { clearTimeout(salvarZoom._t); salvarZoom._t = setTimeout(salvarZoom, 300); });

        viewport.addEventListener('wheel', panzoom.zoomWithWheel);

        document.getElementById('btnZoomIn').addEventListener('click', () => { panzoom.zoomIn(); setTimeout(salvarZoom, 300); });
        document.getElementById('btnZoomOut').addEventListener('click', () => { panzoom.zoomOut(); setTimeout(salvarZoom, 300); });
        document.getElementById('btnZoomReset').addEventListener('click', () => { panzoom.reset(); setTimeout(salvarZoom, 300); });

        // Atrela eventos de arrasto (drag & drop) e tooltip hover nos marcadores
        makeMarkersDraggable();
        window.atualizarLinhasGuia(panzoomTarget);

        let startX = 0;
        let startY = 0;

        panzoomTarget.addEventListener('pointerdown', function(e) {
            startX = e.clientX;
            startY = e.clientY;
        });

        panzoomTarget.addEventListener('panzoomend', function(e) {
            if (modoRecorte) return;
            if (justDragged) {
                setTimeout(() => { justDragged = false; }, 100);
                return;
            }

            const dist = Math.hypot(e.detail.originalEvent.clientX - startX, e.detail.originalEvent.clientY - startY);
            if (dist > 5) return;

            const originalEvent = e.detail.originalEvent;
            const targetElement = document.elementFromPoint(originalEvent.clientX, originalEvent.clientY);

            if (!targetElement) return;

            const marker = targetElement.closest('.marker-ponto');

            if (marker && panzoomTarget.contains(marker)) {
                // MODO EDITAR / EXCLUIR
                abrirFormEdicao(marker, originalEvent);
            } else if (targetElement === svgImage) {
                // MODO CRIAR: o ícone vai cair exatamente onde se clicou
                selecionar(null);
                abrirFormCriacao(telaParaPct(originalEvent.clientX, originalEvent.clientY), originalEvent);
            }
        });
    }

    // ================== Formulário ==================
    // MODO EDIÇÃO
    function abrirFormEdicao(marker, event) {
        selecionar(marker);
        const markerId = marker.dataset.id;
        const markerNome = marker.dataset.nome || ('Ponto #' + markerId);

        formContexto = {
            modo: 'editar',
            marker,
            nome: markerNome,
            icone: centroIconePct(marker),
            posOriginal: marker.dataset.labelposition || 'right',
            fontsizeOriginal: marker.dataset.fontsize || '12',
        };

        document.getElementById('formTitle').innerText = 'Editar Ponto: ' + markerNome;

        mainForm.action = plantaMarkUrl;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('btnSalvar').innerText = 'Atualizar Ponto';

        // Garante ou cria o input hidden com o ID do ponto
        let inputHiddenPonto = document.getElementById('hidden_patch_panel_sala_id');
        if (!inputHiddenPonto) {
            inputHiddenPonto = document.createElement('input');
            inputHiddenPonto.type = 'hidden';
            inputHiddenPonto.id = 'hidden_patch_panel_sala_id';
            inputHiddenPonto.name = 'patch_panel_sala_id';
            mainForm.appendChild(inputHiddenPonto);
        }
        inputHiddenPonto.value = markerId;

        // Coordenadas atuais (só mudam se a posição do rótulo ou a fonte mudarem)
        document.getElementById('inputX').value = parseFloat(marker.style.left) || 0;
        document.getElementById('inputY').value = parseFloat(marker.style.top) || 0;
        document.getElementById('inputDx').value = '';
        document.getElementById('inputDy').value = '';

        divSelectPonto.style.display = 'none';
        selectPonto.required = false;

        divReadonlyPonto.style.display = 'block';
        document.getElementById('inputPontoNome').value = markerNome;

        document.getElementById('tipo_porta_id').value = marker.dataset.tipo || '';
        document.getElementById('comentario').value = marker.dataset.comentario || '';
        document.getElementById('tamanho').value = marker.dataset.tamanho || '';
        document.getElementById('inputFontsize').value = formContexto.fontsizeOriginal;
        document.getElementById('label_position').value = formContexto.posOriginal;

        // Configura rota de remoção
        deleteForm.action = plantaUnmarkUrlTemplate.replace('__ID__', markerId);
        deleteForm.style.display = 'block';

        posicionarPopover(event);
    }

    // MODO CRIAÇÃO
    function abrirFormCriacao(iconePct, event) {
        closeForm();

        const inputHiddenPonto = document.getElementById('hidden_patch_panel_sala_id');
        if (inputHiddenPonto) {
            inputHiddenPonto.remove();
        }

        formContexto = { modo: 'criar', icone: iconePct };

        document.getElementById('formTitle').innerText = 'Selecione o Ponto:';
        mainForm.action = plantaMarkUrl;
        document.getElementById('formMethod').value = 'PUT';

        document.getElementById('inputX').value = iconePct.x.toFixed(2);
        document.getElementById('inputY').value = iconePct.y.toFixed(2);
        document.getElementById('btnSalvar').innerText = 'Salvar Ponto';

        divSelectPonto.style.display = 'block';
        selectPonto.required = true;

        divReadonlyPonto.style.display = 'none';
        deleteForm.style.display = 'none';

        posicionarPopover(event);
    }

    // Antes de enviar: mantém o ícone no lugar mesmo mudando a posição do rótulo/fonte
    mainForm.addEventListener('submit', function (e) {
        if (!formContexto) return;
        const pos = document.getElementById('label_position').value;
        const fontsize = parseFloat(document.getElementById('inputFontsize').value) || 12;
        const inX = document.getElementById('inputX'), inY = document.getElementById('inputY');
        const inDx = document.getElementById('inputDx'), inDy = document.getElementById('inputDy');

        if (formContexto.portaSemSala) {
            e.preventDefault();
            e.stopImmediatePropagation();
            marcarPortaSemSala(pos, fontsize);
            return;
        }

        if (formContexto.modo === 'criar') {
            const opt = selectPonto.options[selectPonto.selectedIndex];
            const nome = opt ? (opt.dataset.nome || opt.text.trim()) : '';
            const p = xyParaIcone(formContexto.icone, nome, fontsize, pos);
            inX.value = p.x.toFixed(2);
            inY.value = p.y.toFixed(2);
            if (pos === 'livre') { inDx.value = '2.2'; inDy.value = '-1.6'; }
            return;
        }

        const m = formContexto.marker;
        const mudou = pos !== formContexto.posOriginal || String(fontsize) !== String(formContexto.fontsizeOriginal);
        if (!mudou) return; // nada que afete a posição: mantém x/y exatamente como estão

        const p = xyParaIcone(formContexto.icone, formContexto.nome, fontsize, pos);
        inX.value = p.x.toFixed(2);
        inY.value = p.y.toFixed(2);

        if (pos === 'livre') {
            // o rótulo continua onde estava, agora ligado por linha guia
            const d = formContexto.posOriginal === 'livre'
                ? { dx: parseFloat(m.dataset.dx), dy: parseFloat(m.dataset.dy) }
                : deslocRotulo(m);
            inDx.value = d.dx.toFixed(2);
            inDy.value = d.dy.toFixed(2);
        }
    });

    function posicionarPopover(event) {
        const viewportRect = viewport.getBoundingClientRect();

        // Tornamos visível temporariamente para medir as dimensões reais do formulário
        popoverForm.style.display = 'block';
        const formWidth = popoverForm.offsetWidth || 280;
        const formHeight = popoverForm.offsetHeight || 380;

        let popoverX = event.clientX - viewportRect.left;
        let popoverY = event.clientY - viewportRect.top;

        if (popoverX + formWidth > viewportRect.width) {
            popoverX -= formWidth;
        }
        if (popoverY + formHeight > viewportRect.height) {
            popoverY -= formHeight;
        }

        popoverX = Math.max(10, popoverX);
        popoverY = Math.max(10, popoverY);

        popoverForm.style.left = popoverX + 'px';
        popoverForm.style.top = popoverY + 'px';
    }

    // ================== Arrastar marcadores (e rótulos livres) ==================
    function makeMarkersDraggable() {
        const markers = panzoomTarget.querySelectorAll('.marker-ponto');

        markers.forEach(marker => {
            marker.style.cursor = 'grab';

            const nome = marker.dataset.nome || '';
            const comentario = marker.dataset.comentario || '';
            let tooltipText = nome;
            if (comentario) {
                tooltipText += `\nObs: ${comentario}`;
            }
            marker.setAttribute('title', tooltipText);

            marker.addEventListener('pointerdown', function(e) {
                e.stopPropagation(); // Impede que o panzoom arraste o mapa
                e.preventDefault();  // Evita a seleção de texto nativa do navegador que bloqueia o drag
                if (modoRecorte) return;

                const currentMarker = this;
                const arrastandoRotulo = currentMarker.classList.contains('marker-livre') && e.target.closest('.marker-label');
                const inicio = estadoDe(currentMarker);
                let isMove = false;
                const startX = e.clientX;
                const startY = e.clientY;
                // diferença entre o ponteiro e o centro do ícone (o ícone não "pula" para o ponteiro)
                const ic = currentMarker.querySelector('.marker-icone').getBoundingClientRect();
                const offX = e.clientX - (ic.left + ic.width / 2);
                const offY = e.clientY - (ic.top + ic.height / 2);
                const xy0 = { x: inicio.x, y: inicio.y };
                const icone0 = centroIconePct(currentMarker);

                panzoom.setOptions({ disablePan: true });
                currentMarker.style.cursor = 'grabbing';
                selecionar(currentMarker);

                const onPointerMove = (moveEvent) => {
                    if (Math.abs(moveEvent.clientX - startX) <= 3 && Math.abs(moveEvent.clientY - startY) <= 3 && !isMove) return;
                    isMove = true;
                    justDragged = true;
                    popoverForm.style.display = 'none';

                    if (arrastandoRotulo) {
                        const icNow = currentMarker.querySelector('.marker-icone').getBoundingClientRect();
                        const est = estadoDe(currentMarker);
                        est.dx = (moveEvent.clientX - (icNow.left + icNow.width / 2)) / icNow.width;
                        est.dy = (moveEvent.clientY - (icNow.top + icNow.height / 2)) / icNow.width;
                        aplicarEstado(currentMarker, est);
                        return;
                    }

                    // move mantendo a mesma distância entre ponteiro e ícone
                    const alvoIcone = telaParaPct(moveEvent.clientX - offX, moveEvent.clientY - offY);
                    const est = estadoDe(currentMarker);
                    est.x = xy0.x + (alvoIcone.x - icone0.x);
                    est.y = xy0.y + (alvoIcone.y - icone0.y);
                    aplicarEstado(currentMarker, est);
                };

                const onPointerUp = (upEvent) => {
                    document.removeEventListener('pointermove', onPointerMove);
                    document.removeEventListener('pointerup', onPointerUp);

                    panzoom.setOptions({ disablePan: false });
                    currentMarker.style.cursor = 'grab';

                    if (isMove) {
                        empilhar(inicio);
                        salvarPosicao(currentMarker.dataset.id, estadoDe(currentMarker)).catch(() => {});
                    } else {
                        abrirFormEdicao(currentMarker, upEvent);
                    }
                };

                document.addEventListener('pointermove', onPointerMove);
                document.addEventListener('pointerup', onPointerUp);
            });
        });
    }

    // ================== Lista lateral: busca + arrastar para a planta ==================
    (function () {
        const lista = document.getElementById('lista');
        const busca = document.getElementById('buscaLista');
        const vazio = document.getElementById('listaVazia');

        busca.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            let visiveis = 0;
            const porGrupo = {};
            lista.querySelectorAll('li:not(.grupo)').forEach(li => {
                const ok = !q || li.dataset.busca.includes(q);
                li.style.display = ok ? '' : 'none';
                if (ok) { visiveis++; porGrupo[li.dataset.grupo] = true; }
            });
            lista.querySelectorAll('li.grupo').forEach(g => g.style.display = porGrupo[g.dataset.grupo] ? '' : 'none');
            if (lista.children.length) {
                vazio.style.display = visiveis ? 'none' : '';
                vazio.textContent = 'Nenhum ponto encontrado.';
            }
        });

        lista.addEventListener('dragstart', function (e) {
            const li = e.target.closest('li[draggable]');
            if (!li) return;
            e.dataTransfer.setData('text/plain', li.dataset.livre ? `livre:${li.dataset.pp}:${li.dataset.porta}` : li.dataset.id);
            e.dataTransfer.effectAllowed = 'copy';
        });

        viewport.addEventListener('dragover', function (e) {
            if (modoRecorte) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            viewport.classList.add('is-drop');
        });
        viewport.addEventListener('dragleave', function (e) {
            if (!viewport.contains(e.relatedTarget)) viewport.classList.remove('is-drop');
        });
        viewport.addEventListener('drop', async function (e) {
            e.preventDefault();
            viewport.classList.remove('is-drop');
            const id = e.dataTransfer.getData('text/plain');
            const li = id.startsWith('livre:')
                ? lista.querySelector(`li[data-pp="${id.split(':')[1]}"][data-porta="${id.split(':')[2]}"]`)
                : lista.querySelector(`li[data-id="${id}"]`);
            if (!li) return;

            const r = svgImage.getBoundingClientRect();
            if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) {
                toast('Solte o ponto em cima da planta.', 'err');
                return;
            }
            // abre o formulário completo (tipo, comentário, comprimento, fonte, legenda...)
            const icone = telaParaPct(e.clientX, e.clientY);
            abrirFormCriacao(icone, e);
            if (li.dataset.livre) {
                prepararPortaSemSala(li, icone, e);
            } else {
                selectPonto.value = li.dataset.id;
                selectPonto.dispatchEvent(new Event('change'));
            }
        });
    })();

    // ================== Porta sem sala: escolher a sala ao soltar ==================
    const SALAS_PREDIO = @json($salasParaSugestao);
    const URL_PATCH_PANELS = @json(url('/patch-panels'));
    const divSalaLivre = document.getElementById('divSalaLivre');

    // sugere a sala marcada nesta planta mais próxima de onde a porta foi solta
    function salaMaisProxima(pct) {
        const w = svgImage.offsetWidth, h = svgImage.offsetHeight;
        let melhor = null, dist = Infinity;
        SALAS_PREDIO.forEach(s => {
            if (s.x === null || s.planta !== PLANTA_ID) return;
            const d = Math.hypot((s.x - pct.x) * w, (s.y - pct.y) * h);
            if (d < dist) { dist = d; melhor = s; }
        });
        return melhor;
    }

    // Formulário de criação para uma porta que ainda não tem sala
    function prepararPortaSemSala(li, icone, evento) {
        if (!SALAS_PREDIO.length) { closeForm(); toast('Cadastre uma sala/ambiente neste prédio primeiro.', 'err'); return; }
        formContexto.portaSemSala = { pp: li.dataset.pp, porta: li.dataset.porta, nome: li.dataset.nome };
        document.getElementById('formTitle').innerText = 'Marcar porta ' + li.dataset.nome;
        divSelectPonto.style.display = 'none';
        selectPonto.required = false;
        divReadonlyPonto.style.display = 'block';
        document.getElementById('inputPontoNome').value = li.dataset.nome + ' (sem sala/ambiente)';
        divSalaLivre.style.display = 'block';
        const sugestao = salaMaisProxima(icone);
        if (sugestao) document.getElementById('salaLivre').value = sugestao.id;
        posicionarPopover(evento); // reposiciona: o formulário ficou mais alto
    }

    // 1) vincula a porta à sala (com tipo, comentário, comprimento)  2) marca na planta
    async function marcarPortaSemSala(pos, fontsize) {
        const ctx = formContexto, alvo = ctx.portaSemSala;
        const btn = document.getElementById('btnSalvar');
        btn.disabled = true;
        try {
            const tipo = document.getElementById('tipo_porta_id').value;
            const tamanho = document.getElementById('tamanho').value;
            const r = await fetch(`${URL_PATCH_PANELS}/${alvo.pp}/porta/${alvo.porta}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    sala_id: Number(document.getElementById('salaLivre').value),
                    tipo_porta_id: tipo ? Number(tipo) : null,
                    comentario: document.getElementById('comentario').value || null,
                    tamanho: tamanho === '' ? null : Number(tamanho),
                }),
            });
            if (!r.ok) throw new Error(await mensagemErro(r, 'Erro ao vincular a porta à sala.'));
            const { id, criado } = await r.json();

            const p = xyParaIcone(ctx.icone, alvo.nome, fontsize, pos);
            const dados = { x: p.x, y: p.y, fontsize: Math.round(fontsize), label_position: pos };
            if (pos === 'livre') { dados.dx = 2.2; dados.dy = -1.6; }
            try {
                await salvarPosicao(id, dados);
            } catch (erroMarcacao) {
                // tudo ou nada: se não marcou, desfaz o vínculo que acabou de ser criado
                if (criado) {
                    await fetch(`${URL_PATCH_PANELS}/${alvo.pp}/porta/${alvo.porta}`, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    }).catch(() => {});
                }
                throw erroMarcacao;
            }

            salvarZoom();
            window.location.reload();
        } catch (err) {
            toast(err.message, 'err');
            btn.disabled = false;
        }
    }

    // ================== Tamanho geral das marcações ==================
    (function () {
        const range = document.getElementById('escala');
        const valor = document.getElementById('escalaValor');
        let timer = null;
        range.addEventListener('input', function () {
            const k = parseFloat(this.value);
            panzoomTarget.style.setProperty('--escala-marcadores', k);
            valor.textContent = k.toFixed(1).replace('.', ',') + '×';
            window.atualizarLinhasGuia(panzoomTarget);
            clearTimeout(timer);
            timer = setTimeout(async () => {
                const r = await fetch(plantaConfigUrl, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ escala_marcadores: k }),
                });
                toast(r.ok ? 'Tamanho geral salvo' : 'Erro ao salvar o tamanho', r.ok ? 'ok' : 'err');
            }, 600);
        });
    })();

    // ================== Recorte das bordas ==================
    const moldura = document.getElementById('moldura');
    const camada = document.getElementById('camada');
    const overlay = document.getElementById('recorte-overlay');
    const rectEl = document.getElementById('recorte-rect');
    const btnRecortar = document.getElementById('btnRecortar');
    const btnSalvarRecorte = document.getElementById('btnSalvarRecorte');
    const btnSemRecorte = document.getElementById('btnSemRecorte');
    const btnCancelarRecorte = document.getElementById('btnCancelarRecorte');
    let recorteNovo = null;

    function desenharRect(r) {
        if (!r) { rectEl.style.display = 'none'; return; }
        rectEl.style.display = 'block';
        rectEl.style.left = r.x + '%';
        rectEl.style.top = r.y + '%';
        rectEl.style.width = r.w + '%';
        rectEl.style.height = r.h + '%';
    }

    function entrarRecorte() {
        modoRecorte = true;
        closeForm();
        selecionar(null);
        // mostra a planta inteira enquanto desenha
        moldura.setAttribute('style', 'position: relative;');
        camada.setAttribute('style', 'position: relative;');
        svgImage.style.height = 'auto';
        overlay.style.display = 'block';
        recorteNovo = RECORTE_ATUAL ? { ...RECORTE_ATUAL } : null;
        desenharRect(recorteNovo);
        panzoom.reset({ animate: false });
        panzoom.setOptions({ disablePan: true });
        btnRecortar.style.display = 'none';
        [btnSalvarRecorte, btnSemRecorte, btnCancelarRecorte].forEach(b => b.style.display = '');
        document.getElementById('ajuda').textContent = 'Arraste sobre a planta para marcar a área que deve aparecer. As marcações não mudam.';
    }

    function sairRecorte() {
        modoRecorte = false;
        moldura.setAttribute('style', ESTILOS_RECORTE.moldura);
        camada.setAttribute('style', ESTILOS_RECORTE.camada);
        svgImage.style.height = RECORTE_ATUAL ? '100%' : 'auto';
        overlay.style.display = 'none';
        panzoom.setOptions({ disablePan: false });
        panzoom.reset({ animate: false });
        btnRecortar.style.display = '';
        [btnSalvarRecorte, btnSemRecorte, btnCancelarRecorte].forEach(b => b.style.display = 'none');
        document.getElementById('ajuda').textContent = 'Clique num ponto para editar · arraste para mover · setas ajustam 1px (Shift: 10px) · Esc solta a seleção';
    }

    overlay.addEventListener('pointerdown', function (e) {
        e.stopPropagation();
        e.preventDefault();
        const a = telaParaPct(e.clientX, e.clientY);
        const mover = ev => {
            const b = telaParaPct(ev.clientX, ev.clientY);
            recorteNovo = { x: Math.min(a.x, b.x), y: Math.min(a.y, b.y), w: Math.abs(b.x - a.x), h: Math.abs(b.y - a.y) };
            desenharRect(recorteNovo);
        };
        const soltar = () => {
            document.removeEventListener('pointermove', mover);
            document.removeEventListener('pointerup', soltar);
        };
        document.addEventListener('pointermove', mover);
        document.addEventListener('pointerup', soltar);
    });

    async function salvarRecorte(valor) {
        const r = await fetch(plantaConfigUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ recorte: valor }),
        });
        if (!r.ok) { toast('Erro ao salvar o recorte', 'err'); return; }
        try { sessionStorage.removeItem(CHAVE_ZOOM); } catch (e) {}
        window.location.reload();
    }

    btnRecortar.addEventListener('click', entrarRecorte);
    btnCancelarRecorte.addEventListener('click', sairRecorte);
    btnSemRecorte.addEventListener('click', () => salvarRecorte(null));
    btnSalvarRecorte.addEventListener('click', () => {
        if (!recorteNovo || recorteNovo.w < 2 || recorteNovo.h < 2) {
            toast('Arraste sobre a planta para marcar a área.', 'err');
            return;
        }
        const r = Object.fromEntries(Object.entries(recorteNovo).map(([k, v]) => [k, Number(v.toFixed(3))]));
        salvarRecorte(r);
    });

    // ================== Select do formulário ==================
    if (selectPonto) {
        selectPonto.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];

            if (this.value) {
                document.getElementById('tipo_porta_id').value = selectedOption.getAttribute('data-tipo') || '';
                document.getElementById('comentario').value = selectedOption.getAttribute('data-comentario') || '';
                document.getElementById('tamanho').value = selectedOption.getAttribute('data-tamanho') || '';
                document.getElementById('inputFontsize').value = selectedOption.getAttribute('data-fontsize') || '12';
                document.getElementById('label_position').value = selectedOption.getAttribute('data-labelposition') || 'right';
            } else {
                limparCamposFormulario();
            }
        });
    }

    // Inicialização
    if (svgImage.complete) {
        initPanzoom();
    } else {
        svgImage.addEventListener('load', initPanzoom);
    }

    // Ao enviar o formulário (recarrega a página), preserva o zoom
    mainForm.addEventListener('submit', () => { if (panzoom) salvarZoom(); });
    deleteForm.addEventListener('submit', () => { if (panzoom) salvarZoom(); });

    function limparCamposFormulario() {
        document.getElementById('tipo_porta_id').value = '';
        document.getElementById('comentario').value = '';
        document.getElementById('tamanho').value = '';
        document.getElementById('inputFontsize').value = '12';
        document.getElementById('label_position').value = 'right';
    }

    function closeForm() {
        popoverForm.style.display = 'none';
        formContexto = null;
        divSalaLivre.style.display = 'none';
        document.getElementById('btnSalvar').disabled = false;
        if (selectPonto) {
            selectPonto.value = '';
        }
        limparCamposFormulario();
    }

    // Bloqueio de Zoom do Navegador (Teclado e Roda do Mouse)
    window.addEventListener('keydown', function (e) {
        if (e.ctrlKey || e.metaKey) {
            const key = e.key;
            const code = e.code;
            if (
                key === '+' || key === '=' ||
                key === '-' || key === '_' ||
                key === '0' ||
                code === 'NumpadAdd' ||
                code === 'NumpadSubtract' ||
                code === 'Minus' ||
                code === 'Equal' ||
                code === 'Digit0'
            ) {
                e.preventDefault();
                e.stopPropagation();
            }
        }
    }, true);

    window.addEventListener('wheel', function (e) {
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
        }
    }, { passive: false });
</script>
@endsection

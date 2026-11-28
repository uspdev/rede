@extends('main')

@section('content')

<br>

<div class="card">
    <div class="card-header bg-usp">
        <div class="d-flex justify-content-between align-items-center">
            <span class="h4 mb-0 text-dark">
                <i class="fas fa-network-wired"></i> Patch Panel: {{ $patchPanel->nome }}
                <small class="text-muted">
                    Rack: {{ $patchPanel->rack->nome }} | Prédio: {{ $patchPanel->rack->predio->nome }}
                </small>
            </span>
            <div class="d-flex gap-2">
                <a href="{{ route('patch-panels.excluir', ['patchPanel' => $patchPanel]) }}" class="btn btn-outline-danger">
                    <i class="fas fa-trash"></i> Excluir
                </a>
                <a href="{{ route('racks.show', ['rack' => $patchPanel->rack]) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="card mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Todas as Portas ({{ $patchPanel->qtde_portas }})</h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th width="70px">Porta</th>
                                <th width="100px">Status</th>
                                <th>Sala/Ambiente Vinculada</th>
                                <th width="190px">Ações</th>
                                <th>Tipo de Porta</th>
                                <th>Switch</th>
                                <th>Comentário</th>
                                <th>Comprimento</th>
                                <th>Planta</th>
                            </tr>
                        </thead>
                        @php
                            $conexoesPorPorta = $patchPanel->conexoes()->with('equipamento')->get()->keyBy('porta_patch_panel');
                        @endphp
                        <tbody>
                            @foreach(range(1, $patchPanel->qtde_portas) as $porta)
                            @php
                                $vinculo = $salasVinculadas->first(fn ($sala) => $sala->pivot->porta == $porta);
                                $tipoPorta = $vinculo ? $vinculo->pivot->tipoPorta : null;
                                $conexao = $conexoesPorPorta->get($porta);
                            @endphp
                            <tr data-porta="{{ $porta }}"
                                data-sala-id="{{ $vinculo?->id }}"
                                data-tipo-id="{{ $vinculo?->pivot->tipo_porta_id }}"
                                data-comentario="{{ $vinculo?->pivot->comentario }}"
                                data-tamanho="{{ $vinculo?->pivot->tamanho }}"
                                data-marcada="{{ $vinculo?->pivot->planta_id ? 1 : 0 }}"
                                data-switch="{{ $conexao ? ($conexao->equipamento?->hostname . ' · porta ' . $conexao->porta_equipamento) : '' }}">
                                <td><strong>{{ $porta }}</strong></td>
                                <td>
                                    @if($vinculo)
                                        <span class="badge badge-success bg-success">Vinculada</span>
                                    @else
                                        <span class="badge badge-secondary bg-secondary">Livre</span>
                                    @endif
                                </td>
                                <td>
                                    @if($vinculo)
                                        {{ $vinculo->nome }} <small class="text-muted">({{ $vinculo->predio->nome }})</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @can('user')
                                    @if($vinculo)
                                        <button type="button" class="btn btn-warning btn-sm py-0 px-2 btn-porta"><i class="fas fa-pen"></i> Editar</button>
                                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-desvincular" title="Desvincular"><i class="fas fa-unlink"></i></button>
                                    @else
                                        <button type="button" class="btn btn-primary btn-sm py-0 px-2 btn-porta"><i class="fas fa-link"></i> Vincular</button>
                                    @endif
                                    @endcan
                                </td>
                                <td>
                                    @if($tipoPorta)
                                        @if($tipoPorta->cor)
                                            <span class="d-inline-block border rounded align-middle me-1" style="width: 16px; height: 16px; background-color: {{ $tipoPorta->cor }};" title="{{ $tipoPorta->cor }}"></span>
                                        @endif
                                        <span class="align-middle">{{ $tipoPorta->nome }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($conexao)
                                        <i class="fas fa-link text-success"></i>
                                        {{ $conexao->equipamento?->hostname }} · porta {{ $conexao->porta_equipamento }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{!! $vinculo?->pivot->comentario ? e($vinculo->pivot->comentario) : '<span class="text-muted">—</span>' !!}</td>
                                <td>{!! $vinculo?->pivot->tamanho ? e($vinculo->pivot->tamanho) . ' m' : '<span class="text-muted">—</span>' !!}</td>
                                <td>
                                    @if($vinculo && $vinculo->pivot->planta_id)
                                        <a href="{{ route('plantas.edit', ['planta' => $vinculo->pivot->planta_id]) }}" class="btn btn-sm btn-info py-0 px-2">Ver Planta</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascripts_bottom')
<style>
    .pv-fundo { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); z-index: 1100; display: none; align-items: center; justify-content: center; padding: 16px; }
    .pv-fundo.show { display: flex; }
    .pv-caixa { background: #fff; border-radius: 10px; width: 100%; max-width: 480px; max-height: 92vh; overflow-y: auto; box-shadow: 0 12px 40px rgba(0,0,0,.25); }
    .pv-cab { padding: 14px 18px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .pv-cab h3 { font-size: 16px; margin: 0; }
    .pv-cab small { color: #64748b; }
    .pv-fechar { border: 0; background: none; font-size: 22px; line-height: 1; color: #64748b; cursor: pointer; }
    .pv-corpo { padding: 14px 18px; display: flex; flex-direction: column; gap: 12px; }
    .pv-corpo label.rot { font-size: 12px; font-weight: 600; color: #334155; margin-bottom: 4px; display: block; }
    .pv-info { font-size: 12px; background: #f1f5f9; border-radius: 6px; padding: 6px 10px; color: #334155; }
    .pv-salas { border: 1px solid #cbd5e1; border-radius: 6px; }
    .pv-salas input { border: 0; border-bottom: 1px solid #e2e8f0; border-radius: 6px 6px 0 0; width: 100%; padding: 6px 10px; font-size: 13px; outline: none; }
    .pv-salas ul { list-style: none; margin: 0; padding: 4px; max-height: 190px; overflow-y: auto; }
    .pv-salas li { padding: 5px 8px; border-radius: 4px; cursor: pointer; font-size: 13px; }
    .pv-salas li small { color: #64748b; }
    .pv-salas li:hover { background: #f1f5f9; }
    .pv-salas li.sel { background: #0d6efd; color: #fff; }
    .pv-salas li.sel small { color: #dbeafe; }
    .pv-tipos { display: flex; flex-wrap: wrap; gap: 6px; }
    .pv-tipos button { border: 1px solid #cbd5e1; background: #fff; border-radius: 999px; padding: 3px 10px 3px 6px; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .pv-tipos button i { width: 12px; height: 12px; border-radius: 3px; border: 1px solid rgba(0,0,0,.25); display: inline-block; }
    .pv-tipos button.sel { border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13,110,253,.25); font-weight: 600; }
    .pv-linha { display: flex; gap: 10px; }
    .pv-linha > div { flex: 1; }
    .pv-erro { font-size: 12px; color: #dc3545; display: none; }
    .pv-rodape { padding: 12px 18px; border-top: 1px solid #e2e8f0; display: flex; gap: 8px; align-items: center; }
    .pv-rodape .esp { flex: 1; }
    tr.pv-atual > td { background: #fff3cd !important; }
</style>

<div class="pv-fundo" id="pv-fundo" role="dialog" aria-modal="true" aria-labelledby="pv-titulo">
    <div class="pv-caixa">
        <div class="pv-cab">
            <div>
                <h3 id="pv-titulo">Porta</h3>
                <small id="pv-sub"></small>
            </div>
            <button type="button" class="pv-fechar" id="pv-fechar" aria-label="Fechar">&times;</button>
        </div>
        <div class="pv-corpo">
            <div class="pv-info" id="pv-switch" style="display: none;"></div>
            <div>
                <label class="rot">Sala/Ambiente *</label>
                <div class="pv-salas">
                    <input type="search" id="pv-busca" placeholder="Digite para buscar sala/ambiente…" autocomplete="off">
                    <ul id="pv-lista">
                        @foreach($salasPredio as $sala)
                            <li data-id="{{ $sala->id }}" data-busca="{{ Str::lower($sala->nome . ' ' . $sala->descricao) }}">
                                {{ $sala->nome }} @if($sala->descricao)<small>— {{ $sala->descricao }}</small>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
                @if($salasPredio->isEmpty())
                    <small class="text-muted">Nenhuma sala/ambiente cadastrada neste prédio.
                        <a href="{{ route('salas.create', ['predio_id' => $patchPanel->rack->predio_id]) }}">Cadastrar</a></small>
                @endif
            </div>
            <div>
                <label class="rot">Tipo de porta</label>
                <div class="pv-tipos" id="pv-tipos">
                    <button type="button" data-id=""><i style="background: transparent;"></i>Sem tipo</button>
                    @foreach($tiposPorta as $tipo)
                        <button type="button" data-id="{{ $tipo->id }}"><i style="background: {{ $tipo->cor ?: 'transparent' }};"></i>{{ $tipo->nome }}</button>
                    @endforeach
                </div>
            </div>
            <div class="pv-linha">
                <div>
                    <label class="rot" for="pv-comentario">Comentário</label>
                    <input type="text" class="form-control form-control-sm" id="pv-comentario" maxlength="255" placeholder="Ex.: atrás da mesa" autocomplete="off">
                </div>
                <div style="flex: 0 0 130px;">
                    <label class="rot" for="pv-tamanho">Comprimento (m)</label>
                    <input type="number" class="form-control form-control-sm" id="pv-tamanho" step="0.01" min="0" autocomplete="off">
                </div>
            </div>
            <div class="pv-erro" id="pv-erro"></div>
        </div>
        <div class="pv-rodape">
            <button type="button" class="btn btn-sm btn-outline-danger" id="pv-remover">Desvincular</button>
            <span class="esp"></span>
            <button type="button" class="btn btn-sm btn-secondary" id="pv-cancelar">Cancelar</button>
            <button type="button" class="btn btn-sm btn-primary" id="pv-salvar">Salvar</button>
        </div>
    </div>
</div>

<script>
(function () {
    const URL_PORTA = @json(url('/patch-panels/' . $patchPanel->id . '/porta'));
    const NOME_PP = @json($patchPanel->nome);
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const fundo = document.getElementById('pv-fundo');
    const lista = document.getElementById('pv-lista');
    const busca = document.getElementById('pv-busca');
    const erro = document.getElementById('pv-erro');
    let linha = null, salaSel = null, tipoSel = '';

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

    function marcarSala(id) {
        salaSel = id ? String(id) : null;
        lista.querySelectorAll('li').forEach(li => li.classList.toggle('sel', li.dataset.id === salaSel));
    }
    function marcarTipo(id) {
        tipoSel = id ? String(id) : '';
        document.querySelectorAll('#pv-tipos button').forEach(b => b.classList.toggle('sel', b.dataset.id === tipoSel));
    }
    function filtrar() {
        const q = busca.value.trim().toLowerCase();
        lista.querySelectorAll('li').forEach(li => li.style.display = !q || li.dataset.busca.includes(q) ? '' : 'none');
    }

    function abrir(tr) {
        linha = tr;
        tr.classList.add('pv-atual');
        const vinculada = !!tr.dataset.salaId;
        document.getElementById('pv-titulo').textContent = `Patch panel ${NOME_PP} · porta ${tr.dataset.porta}`;
        document.getElementById('pv-sub').textContent = vinculada ? 'Vinculada — edite ou desvincule' : 'Porta livre — escolha a sala/ambiente';
        busca.value = '';
        filtrar();
        marcarSala(tr.dataset.salaId);
        marcarTipo(tr.dataset.tipoId);
        document.getElementById('pv-comentario').value = tr.dataset.comentario || '';
        document.getElementById('pv-tamanho').value = tr.dataset.tamanho || '';
        document.getElementById('pv-remover').style.display = vinculada ? '' : 'none';
        const info = document.getElementById('pv-switch');
        info.style.display = tr.dataset.switch ? '' : 'none';
        info.textContent = tr.dataset.switch ? `🔗 Ligada ao switch ${tr.dataset.switch}` : '';
        erro.style.display = 'none';
        fundo.classList.add('show');
        const sel = lista.querySelector('li.sel');
        if (sel) sel.scrollIntoView({ block: 'nearest' });
        setTimeout(() => busca.focus(), 50);
    }
    function fechar() {
        fundo.classList.remove('show');
        if (linha) linha.classList.remove('pv-atual');
        linha = null;
    }
    function mostrarErro(msg) { erro.textContent = msg; erro.style.display = 'block'; }

    async function desvincular(tr) {
        let msg = `Desvincular a porta ${tr.dataset.porta} do patch panel ${NOME_PP} da sala/ambiente?`;
        if (tr.dataset.marcada === '1') msg += '\n\nA marcação desta porta na planta também será removida.';
        if (!confirm(msg)) return;
        try {
            await api('DELETE', `${URL_PORTA}/${tr.dataset.porta}`);
            window.location.reload();
        } catch (e) { alert(e.message); }
    }

    document.querySelectorAll('.btn-porta').forEach(b => b.addEventListener('click', () => abrir(b.closest('tr'))));
    document.querySelectorAll('.btn-desvincular').forEach(b => b.addEventListener('click', () => desvincular(b.closest('tr'))));

    busca.addEventListener('input', filtrar);
    busca.addEventListener('keydown', e => {
        // Enter escolhe a primeira sala/ambiente visível; com uma escolhida, salva
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const visiveis = [...lista.querySelectorAll('li')].filter(li => li.style.display !== 'none');
        if (busca.value.trim() && visiveis.length) marcarSala(visiveis[0].dataset.id);
        else if (salaSel) document.getElementById('pv-salvar').click();
    });
    lista.addEventListener('click', e => { const li = e.target.closest('li'); if (li) marcarSala(li.dataset.id); });
    lista.addEventListener('dblclick', e => { const li = e.target.closest('li'); if (li) { marcarSala(li.dataset.id); document.getElementById('pv-salvar').click(); } });
    document.getElementById('pv-tipos').addEventListener('click', e => { const b = e.target.closest('button'); if (b) marcarTipo(b.dataset.id); });
    document.getElementById('pv-fechar').addEventListener('click', fechar);
    document.getElementById('pv-cancelar').addEventListener('click', fechar);
    fundo.addEventListener('click', e => { if (e.target === fundo) fechar(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && fundo.classList.contains('show')) fechar(); });

    document.getElementById('pv-salvar').addEventListener('click', async function () {
        if (!linha) return;
        if (!salaSel) { mostrarErro('Escolha a sala/ambiente.'); return; }
        this.disabled = true;
        try {
            const tamanho = document.getElementById('pv-tamanho').value;
            await api('PUT', `${URL_PORTA}/${linha.dataset.porta}`, {
                sala_id: Number(salaSel),
                tipo_porta_id: tipoSel ? Number(tipoSel) : null,
                comentario: document.getElementById('pv-comentario').value || null,
                tamanho: tamanho === '' ? null : Number(tamanho),
            });
            window.location.reload();
        } catch (e) {
            mostrarErro(e.message);
            this.disabled = false;
        }
    });
    document.getElementById('pv-remover').addEventListener('click', () => { if (linha) desvincular(linha); });
})();
</script>
@endsection

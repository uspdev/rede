@extends('main')

@section('content')
<div class="card">
    <div class="card-header bg-usp d-flex justify-content-between align-items-center">
        <span class="h4 mb-0 text-dark">
            <i class="fas fa-network-wired"></i> {{ $equipamento->hostname }}
        </span>
        <div class="d-flex gap-2">
            <a href="{{ route('equipamentos.excluir', ['equipamento' => $equipamento]) }}" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i> Excluir</a>
            <a href="#" onclick="history.back(); return false;" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>
    </div>
    <div class="card-body">
        <style>
            .sw-resumo { display: flex; flex-wrap: wrap; gap: 6px 8px; align-items: center; font-size: .875rem; }
            .sw-resumo .item { background: #f5f7f9; border: 1px solid #e3e6ea; border-radius: 999px; padding: 2px 10px; white-space: nowrap; }
            .sw-resumo .item span { color: #6c757d; margin-right: 4px; }
            .sw-resumo a.item { color: inherit; text-decoration: none; }
            .sw-resumo a.item:hover { border-color: #1094ab; background: #e8f4f7; }
        </style>
        @php $modelo = $equipamento->modeloSwitch; $rack = $equipamento->rack; @endphp
        <div class="sw-resumo">
            <span class="item"><span>IP</span><strong>{{ $equipamento->ip ?: '—' }}</strong></span>
            <span class="item"><span>Modelo</span>{{ trim(($modelo?->fabricante ?? '') . ' ' . ($modelo?->nome ?? '')) ?: '—' }}</span>
            <span class="item"><span>Portas</span>{{ $equipamento->qtde_portas }}</span>
            <span class="item"><span>PoE</span>{{ $modelo?->poe ? 'Sim' : 'Não' }}</span>
            <a class="item" href="{{ route('racks.show', ['rack' => $rack]) }}" title="Abrir o rack">
                <span>Rack</span>{{ $rack->nome }}@if($rack->telecomRoom) · {{ $rack->telecomRoom->nome }}@endif · {{ $rack->predio->nome }}
            </a>
            <a href="{{ route('equipamentos.edit', ['equipamento' => $equipamento]) }}" class="small ml-1"><i class="fas fa-pen"></i> editar</a>
        </div>
        @if($equipamento->comentario)
            <div class="small text-muted mt-2"><i class="far fa-comment"></i> {{ $equipamento->comentario }}</div>
        @endif

        <div class="card mt-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Portas ({{ $equipamento->qtde_portas }})</h5>
                <span class="text-muted small">
                    {{ $conexoesPorPorta->count() }} conectada(s) a patch panel ·
                    <a href="{{ route('racks.show', ['rack' => $equipamento->rack]) }}">conectar pelo diagrama do rack</a>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="lote-barra d-flex flex-wrap align-items-center gap-2 px-3 py-2 border-bottom bg-body-tertiary">
                    <span class="small"><strong id="lote-qtd">0</strong> porta(s) selecionada(s)</span>
                    <button type="button" class="btn btn-link btn-sm p-0" id="lote-todas">Selecionar todas</button>
                    <button type="button" class="btn btn-link btn-sm p-0" id="lote-livres">Só as sem tipo</button>
                    <button type="button" class="btn btn-link btn-sm p-0" id="lote-limpar">Limpar seleção</button>
                    <span class="ms-auto d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm" id="lote-tipo" style="min-width: 190px;" autocomplete="off">
                            <option value="">— remover tipo —</option>
                            @foreach($tiposPortaSwitch as $t)
                                <option value="{{ $t->id }}">{{ $t->nome }}@if($t->vlan) (VLAN {{ $t->vlan }})@endif</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-primary btn-sm text-nowrap" id="lote-aplicar" disabled
                                data-url="{{ route('equipamentos.portas.tipo', ['equipamento' => $equipamento]) }}">
                            Aplicar às selecionadas
                        </button>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th width="40px" class="text-center"><input type="checkbox" class="form-check-input solto" id="lote-check-todas" autocomplete="off" title="Selecionar todas"></th>
                                <th width="80px">Porta</th>
                                <th>Status</th>
                                <th>Patch Panel</th>
                                <th>Sala/Ambiente</th>
                                <th>Tipo da porta (switch)</th>
                                <th>Tipo de porta (patch panel)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 1; $i <= $equipamento->qtde_portas; $i++)
                            @php
                                $conexao = $conexoesPorPorta->get($i);
                                $vinculo = $conexao ? $vinculos->get($conexao->patch_panel_id . ':' . $conexao->porta_patch_panel) : null;
                                $tipo = $vinculo?->tipoPorta;
                            @endphp
                            <tr>
                                <td class="text-center"><input type="checkbox" class="form-check-input solto lote-check" autocomplete="off" value="{{ $i }}" aria-label="Selecionar porta {{ $i }}"></td>
                                <td><strong>{{ $i }}</strong></td>
                                <td>
                                    @if($conexao)
                                        <span class="badge bg-success"><i class="fas fa-link"></i> Conectada</span>
                                    @else
                                        <span class="badge bg-secondary">Livre</span>
                                    @endif
                                </td>
                                <td>
                                    @if($conexao)
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span>
                                                <a href="{{ route('patch-panels.show', ['patchPanel' => $conexao->patchPanel]) }}">{{ $conexao->patchPanel->nome }}</a>
                                                · porta <strong>{{ $conexao->porta_patch_panel }}</strong>
                                                @if($conexao->patchPanel->rack_id !== $equipamento->rack_id)
                                                    <small class="text-muted">(rack {{ $conexao->patchPanel->rack->nome }})</small>
                                                @endif
                                            </span>
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-desconectar"
                                                    title="Desconectar do patch panel"
                                                    data-url="{{ route('conexoes.destroy', ['conexao' => $conexao]) }}"
                                                    data-desc="porta {{ $i }} ↔ {{ $conexao->patchPanel->nome }} porta {{ $conexao->porta_patch_panel }}">
                                                <i class="fas fa-unlink"></i> Desconectar
                                            </button>
                                        </div>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-success py-0 px-2 btn-conectar" data-porta="{{ $i }}"
                                                title="Conectar esta porta a uma porta de patch panel">
                                            <i class="fas fa-link"></i> Conectar
                                        </button>
                                    @endif
                                </td>
                                <td>
                                    @if($vinculo?->sala)
                                        {{ $vinculo->sala->nome }}
                                        @if($vinculo->sala->predio)
                                            <small class="text-muted">({{ $vinculo->sala->predio->nome }})</small>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @php $tipoSwId = $tiposPorPorta->get($i); @endphp
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="d-inline-block border rounded sw-tipo-cor" style="width: 16px; height: 16px; background-color: {{ $tiposPortaSwitch->firstWhere('id', $tipoSwId)?->cor ?? 'transparent' }};"></span>
                                        <select class="form-select form-select-sm sw-tipo-select" style="min-width: 150px;" autocomplete="off"
                                                data-url="{{ route('equipamentos.porta.tipo', ['equipamento' => $equipamento, 'porta' => $i]) }}"
                                                data-anterior="{{ $tipoSwId }}">
                                            <option value="">— sem tipo —</option>
                                            @foreach($tiposPortaSwitch as $t)
                                                <option value="{{ $t->id }}" data-cor="{{ $t->cor }}" @selected($t->id == $tipoSwId)>
                                                    {{ $t->nome }}@if($t->vlan) (VLAN {{ $t->vlan }})@endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    @if($tipo)
                                        @if($tipo->cor)
                                            <span class="d-inline-block border rounded align-middle me-1" style="width: 16px; height: 16px; background-color: {{ $tipo->cor }};"></span>
                                        @endif
                                        <span class="align-middle">{{ $tipo->nome }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            @endfor
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
    .lote-fundo { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); z-index: 1100; display: none; align-items: center; justify-content: center; padding: 16px; }
    .lote-fundo.show { display: flex; }
    .lote-caixa { background: #fff; border-radius: 10px; width: 100%; max-width: 460px; box-shadow: 0 12px 40px rgba(0,0,0,.25); }
    .lote-caixa .cab { padding: 14px 18px; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
    .lote-caixa .corpo { padding: 14px 18px; font-size: 14px; }
    .lote-caixa .corpo small { color: #64748b; }
    #lote-aviso { position: fixed; right: 20px; bottom: 20px; z-index: 1090; background: #198754; color: #fff; padding: 10px 14px; border-radius: 6px; font-size: 14px; box-shadow: 0 4px 12px rgba(0,0,0,.2); opacity: 0; transform: translateY(10px); transition: opacity .2s, transform .2s; pointer-events: none; }
    #lote-aviso.show { opacity: 1; transform: none; }
    tr.lote-ok > td { background-color: #d1e7dd !important; transition: background-color .3s; }
    .lote-caixa .rodape { padding: 12px 18px; border-top: 1px solid #e2e8f0; display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
</style>
<div class="lote-fundo" id="lote-modal" role="dialog" aria-modal="true" aria-labelledby="lote-modal-titulo">
    <div class="lote-caixa">
        <div class="cab" id="lote-modal-titulo">Portas que já têm tipo</div>
        <div class="corpo">
            <p class="mb-2" id="lote-modal-texto"></p>
            <small id="lote-modal-lista"></small>
        </div>
        <div class="rodape">
            <button type="button" class="btn btn-secondary btn-sm" data-escolha="cancelar">Cancelar</button>
            <button type="button" class="btn btn-outline-primary btn-sm" data-escolha="ignorar" id="lote-btn-ignorar">Ignorar as já marcadas</button>
            <button type="button" class="btn btn-primary btn-sm" data-escolha="substituir">Substituir também</button>
        </div>
    </div>
</div>
<div class="lote-fundo" id="con-modal" role="dialog" aria-modal="true" aria-labelledby="con-titulo">
    <div class="lote-caixa">
        <div class="cab" id="con-titulo">Conectar porta</div>
        <div class="corpo">
            @if(empty($patchPanelsParaConectar))
                <div class="alert alert-info mb-0">Nenhum patch panel cadastrado neste prédio.</div>
            @else
                <div class="form-group mb-3">
                    <label for="con-pp" class="small font-weight-bold mb-1">Patch panel</label>
                    <select id="con-pp" class="form-select form-select-sm"></select>
                </div>
                <div class="form-group mb-0">
                    <label for="con-porta" class="small font-weight-bold mb-1">Porta do patch panel</label>
                    <select id="con-porta" class="form-select form-select-sm"></select>
                    <small class="text-muted" id="con-livres"></small>
                </div>
                <div class="text-danger small mt-2" id="con-erro" style="display: none;"></div>
            @endif
        </div>
        <div class="rodape">
            <button type="button" class="btn btn-secondary btn-sm" id="con-cancelar">Cancelar</button>
            @unless(empty($patchPanelsParaConectar))
                <button type="button" class="btn btn-success btn-sm" id="con-salvar"><i class="fas fa-link"></i> Conectar</button>
            @endunless
        </div>
    </div>
</div>
<script>
// ---------- Conectar porta do switch a uma porta de patch panel ----------
(function () {
    const PPS = @json($patchPanelsParaConectar);
    const EQUIP_ID = {{ $equipamento->id }};
    const URL_CONEXOES = @json(route('conexoes.store'));
    const modal = document.getElementById('con-modal');
    const selPP = document.getElementById('con-pp');
    const selPorta = document.getElementById('con-porta');
    const erro = document.getElementById('con-erro');
    const salvar = document.getElementById('con-salvar');
    let portaSw = null;

    function fechar() { modal.classList.remove('show'); portaSw = null; }

    function preencherPortas() {
        const pp = PPS.find(p => String(p.id) === selPP.value);
        selPorta.innerHTML = '';
        if (!pp) return;
        let livres = 0, primeiraLivre = null;
        for (let n = 1; n <= pp.portas; n++) {
            const o = document.createElement('option');
            o.value = n;
            const ocupada = pp.ocupadas[n];
            const sala = pp.salas[n];
            o.textContent = `Porta ${n}` + (sala ? ` — ${sala}` : '') + (ocupada ? ` (ocupada: ${ocupada})` : '');
            o.disabled = !!ocupada;
            if (!ocupada) { livres++; if (primeiraLivre === null) primeiraLivre = n; }
            selPorta.appendChild(o);
        }
        if (primeiraLivre !== null) selPorta.value = primeiraLivre;
        document.getElementById('con-livres').textContent = `${livres} de ${pp.portas} porta(s) livre(s)`;
        salvar.disabled = livres === 0;
    }

    if (selPP) {
        // patch panels agrupados por rack (o rack deste switch primeiro)
        const grupos = {};
        PPS.forEach(pp => (grupos[pp.rack] = grupos[pp.rack] || []).push(pp));
        Object.entries(grupos).forEach(([rack, lista]) => {
            const g = document.createElement('optgroup');
            g.label = 'Rack ' + rack + (lista[0].mesmoRack ? ' (este rack)' : '');
            lista.forEach(pp => {
                const o = document.createElement('option');
                o.value = pp.id;
                o.textContent = `${pp.nome} (${pp.portas - Object.keys(pp.ocupadas).length} livres)`;
                g.appendChild(o);
            });
            selPP.appendChild(g);
        });
        selPP.addEventListener('change', preencherPortas);
    }

    document.querySelectorAll('.btn-conectar').forEach(btn => btn.addEventListener('click', () => {
        portaSw = Number(btn.dataset.porta);
        document.getElementById('con-titulo').textContent = `Conectar a porta ${portaSw} do switch`;
        if (erro) erro.style.display = 'none';
        if (selPP) preencherPortas();
        modal.classList.add('show');
    }));
    document.getElementById('con-cancelar').addEventListener('click', fechar);
    modal.addEventListener('click', e => { if (e.target === modal) fechar(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('show')) fechar(); });

    if (salvar) salvar.addEventListener('click', function () {
        if (!portaSw || !selPorta.value) return;
        salvar.disabled = true;
        fetch(URL_CONEXOES, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                equipamento_id: EQUIP_ID,
                porta_equipamento: portaSw,
                patch_panel_id: Number(selPP.value),
                porta_patch_panel: Number(selPorta.value)
            })
        }).then(r => r.json().catch(() => ({})).then(d => {
            if (!r.ok) throw new Error(d.message || 'Erro ao conectar');
            window.location.reload();
        })).catch(e => {
            erro.textContent = e.message;
            erro.style.display = 'block';
            salvar.disabled = false;
        });
    });
})();

// ---------- Tipo em lote ----------
(function () {
    const checks = [...document.querySelectorAll('.lote-check')];
    const checkTodas = document.getElementById('lote-check-todas');
    const qtd = document.getElementById('lote-qtd');
    const aplicar = document.getElementById('lote-aplicar');
    const selTipo = document.getElementById('lote-tipo');
    let ultimo = null;

    function atualizar() {
        const n = checks.filter(c => c.checked).length;
        qtd.textContent = n;
        aplicar.disabled = n === 0;
        checkTodas.checked = n === checks.length && n > 0;
        checkTodas.indeterminate = n > 0 && n < checks.length;
        checks.forEach(c => c.closest('tr').classList.toggle('table-active', c.checked));
    }
    function marcar(fn) { checks.forEach(c => c.checked = fn(c)); atualizar(); }

    // Shift+clique seleciona a faixa desde a última porta clicada
    checks.forEach((c, idx) => c.addEventListener('click', e => {
        if (e.shiftKey && ultimo !== null) {
            const [a, b] = [Math.min(ultimo, idx), Math.max(ultimo, idx)];
            for (let k = a; k <= b; k++) checks[k].checked = c.checked;
        }
        ultimo = idx;
        atualizar();
    }));
    checkTodas.addEventListener('change', () => marcar(() => checkTodas.checked));
    document.getElementById('lote-todas').addEventListener('click', () => marcar(() => true));
    document.getElementById('lote-limpar').addEventListener('click', () => marcar(() => false));
    document.getElementById('lote-livres').addEventListener('click', () =>
        marcar(c => !c.closest('tr').querySelector('.sw-tipo-select').value));

    // Pergunta o que fazer com as portas que já têm outro tipo: resolve 'substituir' | 'ignorar' | 'cancelar'
    const modal = document.getElementById('lote-modal');
    function perguntar(marcadas, total, nomeTipo) {
        document.getElementById('lote-modal-texto').textContent =
            `${marcadas.length} das ${total} porta(s) selecionada(s) já têm outro tipo. Aplicar "${nomeTipo}" nelas também?`;
        document.getElementById('lote-modal-lista').textContent =
            'Já marcadas: ' + marcadas.map(m => `${m.porta} (${m.tipo})`).join(', ');
        document.getElementById('lote-btn-ignorar').disabled = marcadas.length === total;
        document.getElementById('lote-btn-ignorar').title = marcadas.length === total ? 'Todas as selecionadas já têm tipo' : '';
        modal.classList.add('show');
        return new Promise(resolve => {
            function fim(escolha) {
                modal.classList.remove('show');
                modal.removeEventListener('click', clique);
                document.removeEventListener('keydown', tecla);
                resolve(escolha);
            }
            function clique(e) {
                const b = e.target.closest('[data-escolha]');
                if (b && !b.disabled) fim(b.dataset.escolha);
                else if (e.target === modal) fim('cancelar');
            }
            function tecla(e) { if (e.key === 'Escape') fim('cancelar'); }
            modal.addEventListener('click', clique);
            document.addEventListener('keydown', tecla);
        });
    }

    aplicar.addEventListener('click', async function () {
        let portas = checks.filter(c => c.checked).map(c => Number(c.value));
        const nomeTipo = selTipo.value ? selTipo.selectedOptions[0].textContent.trim() : null;

        if (nomeTipo) {
            // portas selecionadas que já têm um tipo diferente do escolhido
            const marcadas = checks.filter(c => {
                if (!c.checked) return false;
                const v = c.closest('tr').querySelector('.sw-tipo-select').value;
                return v && v !== selTipo.value;
            }).map(c => {
                const sel = c.closest('tr').querySelector('.sw-tipo-select');
                return { porta: Number(c.value), tipo: sel.selectedOptions[0].textContent.trim() };
            });

            if (marcadas.length) {
                const escolha = await perguntar(marcadas, portas.length, nomeTipo);
                if (escolha === 'cancelar') return;
                if (escolha === 'ignorar') {
                    const pular = new Set(marcadas.map(m => m.porta));
                    portas = portas.filter(p => !pular.has(p));
                }
            } else if (!confirm(`Definir o tipo "${nomeTipo}" em ${portas.length} porta(s)?`)) {
                return;
            }
        } else if (!confirm(`Remover o tipo de ${portas.length} porta(s)?`)) {
            return;
        }

        if (!portas.length) return;
        aplicar.disabled = true;
        fetch(aplicar.dataset.url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ portas: portas, tipo_porta_switch_id: selTipo.value ? Number(selTipo.value) : null })
        }).then(r => r.json().catch(() => ({})).then(d => {
            if (!r.ok) throw new Error(d.message || 'Erro ao aplicar o tipo');
            // Atualiza as linhas na própria página (sem depender do reload)
            const aplicadas = new Set(portas);
            const opt = selTipo.value ? selTipo.selectedOptions[0] : null;
            const cor = opt ? ([...document.querySelector('.sw-tipo-select').options].find(o => o.value === selTipo.value)?.dataset.cor || 'transparent') : 'transparent';
            checks.forEach(c => {
                if (!aplicadas.has(Number(c.value))) return;
                const linha = c.closest('tr');
                const sel = linha.querySelector('.sw-tipo-select');
                sel.value = selTipo.value;
                sel.dataset.anterior = selTipo.value;
                linha.querySelector('.sw-tipo-cor').style.backgroundColor = cor;
                linha.classList.add('lote-ok');
                setTimeout(() => linha.classList.remove('lote-ok'), 1500);
            });
            marcar(() => false);
            avisar(opt ? `Tipo "${opt.textContent.trim()}" aplicado em ${portas.length} porta(s).` : `Tipo removido de ${portas.length} porta(s).`);
        })).catch(e => {
            alert(e.message);
        }).finally(() => {
            atualizar();
        });
    });

    function avisar(texto) {
        let t = document.getElementById('lote-aviso');
        if (!t) {
            t = document.createElement('div');
            t.id = 'lote-aviso';
            document.body.appendChild(t);
        }
        t.textContent = texto;
        t.classList.add('show');
        clearTimeout(t._timer);
        t._timer = setTimeout(() => t.classList.remove('show'), 3000);
    }

    atualizar();
})();

document.querySelectorAll('.sw-tipo-select').forEach(function (sel) {
    sel.addEventListener('change', function () {
        const swatch = sel.parentElement.querySelector('.sw-tipo-cor');
        sel.disabled = true;
        fetch(sel.dataset.url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ tipo_porta_switch_id: sel.value ? Number(sel.value) : null })
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (d) {
                if (!r.ok) throw new Error(d.message || 'Erro ao salvar o tipo da porta');
            });
        }).then(function () {
            sel.dataset.anterior = sel.value;
            const opt = sel.selectedOptions[0];
            swatch.style.backgroundColor = (opt && opt.dataset.cor) || 'transparent';
        }).catch(function (e) {
            alert(e.message);
            sel.value = sel.dataset.anterior || '';
        }).finally(function () {
            sel.disabled = false;
        });
    });
});

document.querySelectorAll('.btn-desconectar').forEach(function (btn) {
    btn.addEventListener('click', function () {
        if (!confirm('Desconectar ' + btn.dataset.desc + '?')) return;
        btn.disabled = true;
        fetch(btn.dataset.url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        }).then(function (r) {
            if (!r.ok) throw new Error('Erro ao desconectar');
            window.location.reload();
        }).catch(function (e) {
            alert(e.message);
            btn.disabled = false;
        });
    });
});
</script>
@endsection

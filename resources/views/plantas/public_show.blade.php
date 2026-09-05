@extends('main')

@section('content')
<!-- Inclusão do Panzoom via CDN -->
<script src="https://unpkg.com/@panzoom/panzoom@4.5.1/dist/panzoom.min.js"></script>

@php
    // Obtém os tipos de porta únicos presentes nos pontos
    $tiposPorta = $markers->pluck('tipoPorta')->unique('id')->filter();
@endphp

<div class="container-fluid py-3">
    <!-- Controles Superiores -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <!-- Botão Voltar -->
        <a href="/plantas/{{ $planta->predio_id }}" style="text-decoration: none; color: #000; background-color: #f0f0f0; padding: 6px 12px; border-radius: 4px; font-size: 14px;">
            &larr; Voltar
        </a>

        <!-- Lado Direito: Controles de Zoom + Botão PDF -->
        <div style="display: flex; gap: 10px; align-items: center;">
            
            <!-- Botão Gerar PDF -->
            <a href="/plantas/pdf/{{ $planta->id }}" target="_blank" style="text-decoration: none; color: #fff; background-color: #dc2626; padding: 6px 12px; border-radius: 4px; font-size: 13px; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                📄 Exportar PDF
            </a>

            <!-- Barra de Controles de Zoom -->
            <div style="display: flex; gap: 5px; align-items: center; background: #f8fafc; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
                <button type="button" id="btnZoomIn" style="padding: 4px 10px; font-weight: bold; cursor: pointer;">+</button>
                <button type="button" id="btnZoomOut" style="padding: 4px 10px; font-weight: bold; cursor: pointer;">-</button>
                <button type="button" id="btnZoomReset" style="padding: 4px 10px; cursor: pointer; font-size: 12px;">Redefinir Zoom</button>
                <span style="font-size: 11px; color: #64748b; margin-left: 5px;">(Use o scroll do mouse ou clique e arraste para mover)</span>
            </div>

        </div>
    </div>

    <!-- Painel de Legenda e Filtros por Tipo de Porta -->
    <div class="card mb-3 shadow-sm border-0 bg-white">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <strong class="text-dark" style="font-size: 14px;">Filtro / Legenda:</strong>
                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none ms-2" id="btnSelectAll" style="font-size: 12px;">Todos</button>
                    <span class="text-muted">|</span>
                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-muted" id="btnDeselectAll" style="font-size: 12px;">Nenhum</button>
                </div>
                
                <div class="d-flex flex-wrap align-items-center gap-3">
                    @foreach($tiposPorta as $tipo)
                        <label class="d-flex align-items-center gap-1 style-cursor-pointer user-select-none mb-0" style="cursor: pointer; font-size: 13px;">
                            <input type="checkbox" class="filtro-tipo-checkbox" value="{{ $tipo->id }}" checked>
                            <!-- Ícone de Triângulo simulando a cor da porta -->
                            <span style="display: inline-block; width: 0; height: 0; border-left: 6px solid transparent; border-right: 6px solid transparent; border-bottom: 12px solid {{ $tipo->cor }}; margin: 0 3px;"></span>
                            <span class="fw-medium">{{ $tipo->nome }}</span>
                        </label>
                    @endforeach

                    <!-- Opção caso exista algum ponto sem tipo cadastrado -->
                    <label class="d-flex align-items-center gap-1 style-cursor-pointer user-select-none mb-0" style="cursor: pointer; font-size: 13px;">
                        <input type="checkbox" class="filtro-tipo-checkbox" value="0" checked>
                        <span style="display: inline-block; width: 0; height: 0; border-left: 6px solid transparent; border-right: 6px solid transparent; border-bottom: 12px solid #ef4444; margin: 0 3px;"></span>
                        <span class="text-muted">Não definido</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Viewport fixa para conter o zoom -->
    <div id="viewport" style="position: relative; width: 100%; height: 75vh; overflow: hidden; border: 1px solid #ccc; background-color: #f8fafc; border-radius: 6px;" class="mb-4">

        <!-- Target do Panzoom -->
        <div id="panzoom-target" style="position: relative; width: 100%; transform-origin: 0 0; cursor: grab;">

            <!-- Imagem SVG da Planta Baixa -->
            <img id="svg-image" src="{{ '/plantas/' . $planta->predio_id . '/' . $planta->id }}" style="width: 100%; height: auto; display: block; user-select: none;" draggable="false" alt="{{ $planta->name }}">

            <!-- Renderização dos Marcadores Visíveis -->
            @include('plantas.partials.markers', ['markers' => $markers])
            @include('salas.partials.markers', ['salas' => $planta->salas])
        </div>
    </div>

    <!-- Tabela Detalhada de Pontos Visíveis Agrupados por Sala -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Detalhamento dos Pontos por Sala</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ponto</th>
                            <th>Sala</th>
                            <th>Tipo</th>
                            <th>Comprimento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($markers->groupBy(fn($item) => optional($item->sala)->nome ?? 'Sem Sala Definida') as $nomeSala => $pontosDaSala)
                            @php
                                $salaObj = $pontosDaSala->first()?->sala;
                                $salaKey = Str::slug($nomeSala);
                            @endphp
                            <!-- Cabeçalho da Sala -->
                            <tr class="table-secondary sala-header-row" data-sala-key="{{ $salaKey }}">
                                <td colspan="4" class="fw-bold text-uppercase py-2 text-center">
                                    <i class="bi bi-door-closed me-1"></i> {{ $nomeSala }}
                                    @if($salaObj && !empty($salaObj->descricao))
                                        <span class="text-muted fw-normal text-lowercase"> — {{ $salaObj->descricao }} - Quantidade de pontos na sala: <b>{{$pontosDaSala->count()}}</b></span>
                                    @endif
                                </td>
                            </tr>

                            <!-- Pontos Pertencentes à Sala -->
                            @foreach($pontosDaSala as $ponto)
                                @php
                                    $nomePonto = optional(optional($ponto->patchPanel)->rack)->nome . '-' . optional($ponto->patchPanel)->nome . '-' . $ponto->porta;
                                    $corTipo = optional($ponto->tipoPorta)->cor ?? '#ef4444';
                                    $tipoId = $ponto->tipo_porta_id ?? 0;
                                @endphp
                                <tr class="ponto-row" data-tipo-id="{{ $tipoId }}" data-sala-key="{{ $salaKey }}" data-tamanho="{{ $ponto->tamanho ?? 0 }}">
                                    <td class="fw-bold ps-4">{{ $nomePonto }}</td>
                                    <td>{{ optional($ponto->sala)->nome ?? '-' }}</td>
                                    <td>
                                        <span class="badge" style="background-color: {{ $corTipo }}; font-weight: 500;">
                                            {{ optional($ponto->tipoPorta)->nome ?? 'Não definido' }}
                                        </span>
                                    </td>
                                    <td>{{ $ponto->tamanho ? number_format($ponto->tamanho, 2, ',', '.') . ' m' : '-' }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    Nenhum ponto visível nesta planta.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <!-- Rodapé da Tabela com Total de Pontos e Soma dos Comprimentos -->
                    <tfoot class="table-dark">
                        <tr>
                            <td colspan="2" class="fw-bold">
                                Total de Pontos: <span class="badge bg-light text-dark ms-1" id="total-pontos-visiveis">{{ $markers->count() }}</span>
                            </td>
                            <td class="text-end fw-bold">Comprimento Total de Cabeamento:</td>
                            <td class="fw-bold fs-6" id="soma-comprimento-visivel">
                                {{ number_format($markers->sum('tamanho'), 2, ',', '.') }} m
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const viewport = document.getElementById('viewport');
    const panzoomTarget = document.getElementById('panzoom-target');
    const svgImage = document.getElementById('svg-image');

    let panzoom;

    function initPanzoom() {
        panzoom = Panzoom(panzoomTarget, {
            maxScale: 6,
            minScale: 0.8,
            contain: 'outside'
        });

        setTimeout(() => { panzoom.reset(); }, 50);

        viewport.addEventListener('wheel', panzoom.zoomWithWheel);

        document.getElementById('btnZoomIn').addEventListener('click', panzoom.zoomIn);
        document.getElementById('btnZoomOut').addEventListener('click', panzoom.zoomOut);
        document.getElementById('btnZoomReset').addEventListener('click', panzoom.reset);
    }

    if (svgImage.complete) {
        initPanzoom();
    } else {
        svgImage.addEventListener('load', initPanzoom);
    }

    window.addEventListener('keydown', function (e) {
        if (
            (e.ctrlKey || e.metaKey) &&
            (
                e.key === '+' ||
                e.key === '-' ||
                e.key === '=' ||
                e.key === '0' ||
                e.code === 'NumpadAdd' ||
                e.code === 'NumpadSubtract'
            )
        ) {
            e.preventDefault();
        }
    }, { passive: false });

    window.addEventListener('wheel', function (e) {
        if (e.ctrlKey) {
            e.preventDefault();
        }
    }, { passive: false });

    // --- LÓGICA DE FILTRAGEM POR TIPO DE PORTA ---
    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.filtro-tipo-checkbox');
        const btnSelectAll = document.getElementById('btnSelectAll');
        const btnDeselectAll = document.getElementById('btnDeselectAll');

        function aplicarFiltro() {
            // Obtém IDs selecionados como Strings
            const tiposSelecionados = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);

            // 1. Ocultar/Mostrar Marcadores no SVG
            const marcadores = document.querySelectorAll('.ponto-marker');
            marcadores.forEach(marker => {
                const tipoId = marker.getAttribute('data-tipo-id') || '0';
                if (tiposSelecionados.includes(tipoId)) {
                    marker.style.display = '';
                } else {
                    marker.style.display = 'none';
                }
            });

            // 2. Ocultar/Mostrar Linhas na Tabela e Calcular Totais
            let totalPontos = 0;
            let somaComprimento = 0;

            const pontoRows = document.querySelectorAll('.ponto-row');
            pontoRows.forEach(row => {
                const tipoId = row.getAttribute('data-tipo-id') || '0';
                const tamanho = parseFloat(row.getAttribute('data-tamanho')) || 0;

                if (tiposSelecionados.includes(tipoId)) {
                    row.style.display = '';
                    totalPontos++;
                    somaComprimento += tamanho;
                } else {
                    row.style.display = 'none';
                }
            });

            // 3. Ocultar cabeçalhos de salas que ficaram sem nenhum ponto visível
            const salaHeaders = document.querySelectorAll('.sala-header-row');
            salaHeaders.forEach(header => {
                const salaKey = header.getAttribute('data-sala-key');
                const pontosVisiveisNaSala = document.querySelectorAll(`.ponto-row[data-sala-key="${salaKey}"]:not([style*="display: none"])`);
                
                if (pontosVisiveisNaSala.length > 0) {
                    header.style.display = '';
                } else {
                    header.style.display = 'none';
                }
            });

            // 4. Atualizar Rodapé da Tabela com Totais Filtrados
            const elTotal = document.getElementById('total-pontos-visiveis');
            const elSoma = document.getElementById('soma-comprimento-visivel');

            if (elTotal) elTotal.textContent = totalPontos;
            if (elSoma) {
                elSoma.textContent = somaComprimento.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' m';
            }
        }

        // Eventos
        checkboxes.forEach(cb => cb.addEventListener('change', aplicarFiltro));

        if (btnSelectAll) {
            btnSelectAll.addEventListener('click', () => {
                checkboxes.forEach(cb => cb.checked = true);
                aplicarFiltro();
            });
        }

        if (btnDeselectAll) {
            btnDeselectAll.addEventListener('click', () => {
                checkboxes.forEach(cb => cb.checked = false);
                aplicarFiltro();
            });
        }
    });
</script>
@endsection
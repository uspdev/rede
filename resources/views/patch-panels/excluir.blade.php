@extends('main')

@section('content')
@php
    $totalVinculos = $vinculosSalas->count() + $conexoes->count();
    $marcadosEmPlanta = $vinculosSalas->filter(fn ($v) => $v->planta_id && $v->x !== null)->count();
@endphp

<br>

<div class="card border-danger">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <span class="h5 mb-0">
            <i class="fas fa-exclamation-triangle"></i> Excluir patch panel: {{ $patchPanel->nome }}
        </span>
        <a href="{{ route('racks.show', ['rack' => $patchPanel->rack]) }}" class="btn btn-light btn-sm">
            <i class="fas fa-arrow-left"></i> Voltar sem excluir
        </a>
    </div>

    <div class="card-body">
        <p class="mb-1">
            <strong>Rack:</strong> {{ $patchPanel->rack->nome }} ·
            <strong>Prédio:</strong> {{ $patchPanel->rack->predio->nome ?? '—' }} ·
            <strong>Portas:</strong> {{ $patchPanel->qtde_portas }}
        </p>

        @if($totalVinculos === 0)
            <div class="alert alert-success mt-3 mb-4">
                Este patch panel não tem nenhuma porta vinculada. Só o patch panel será excluído.
            </div>
        @else
            <div class="alert alert-warning mt-3 mb-4">
                <strong>Atenção:</strong> ao excluir este patch panel, também serão apagados
                <strong>{{ $vinculosSalas->count() }}</strong> vínculo(s) com salas/ambientes
                @if($marcadosEmPlanta)
                    (<strong>{{ $marcadosEmPlanta }}</strong> marcado(s) em plantas, que somem das plantas)
                @endif
                e <strong>{{ $conexoes->count() }}</strong> conexão(ões) com switches.
                Confira a lista abaixo. <strong>Não é possível desfazer.</strong>
            </div>
        @endif

        @if($vinculosSalas->isNotEmpty())
            <h2 class="h6 mt-4"><i class="fas fa-door-open"></i> Vínculos com salas/ambientes ({{ $vinculosSalas->count() }})</h2>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="80px">Porta</th>
                            <th>Sala/Ambiente</th>
                            <th>Tipo de Porta</th>
                            <th>Comentário</th>
                            <th>Comprimento</th>
                            <th>Planta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vinculosSalas as $vinculo)
                            <tr>
                                <td><strong>{{ $vinculo->porta }}</strong></td>
                                <td>
                                    {{ $vinculo->sala->nome ?? '—' }}
                                    @if($vinculo->sala?->predio)
                                        <small class="text-muted">({{ $vinculo->sala->predio->nome }})</small>
                                    @endif
                                </td>
                                <td>
                                    @if($vinculo->tipoPorta)
                                        @if($vinculo->tipoPorta->cor)
                                            <span class="d-inline-block border rounded align-middle me-1" style="width: 14px; height: 14px; background-color: {{ $vinculo->tipoPorta->cor }};"></span>
                                        @endif
                                        <span class="align-middle">{{ $vinculo->tipoPorta->nome }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $vinculo->comentario ?: '—' }}</td>
                                <td>{{ $vinculo->tamanho ? number_format($vinculo->tamanho, 2, ',', '.') . ' m' : '—' }}</td>
                                <td>
                                    @if($vinculo->planta && $vinculo->x !== null)
                                        <span class="badge bg-info text-dark"><i class="fas fa-map-marker-alt"></i> {{ $vinculo->planta->name }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($conexoes->isNotEmpty())
            <h2 class="h6 mt-4"><i class="fas fa-network-wired"></i> Conexões com switches ({{ $conexoes->count() }})</h2>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="160px">Porta do patch panel</th>
                            <th>Switch</th>
                            <th>Porta do switch</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conexoes as $conexao)
                            <tr>
                                <td><strong>{{ $conexao->porta_patch_panel }}</strong></td>
                                <td>
                                    {{ $conexao->equipamento->hostname ?? '—' }}
                                    @if($conexao->equipamento?->rack && $conexao->equipamento->rack_id !== $patchPanel->rack_id)
                                        <small class="text-muted">(rack {{ $conexao->equipamento->rack->nome }})</small>
                                    @endif
                                </td>
                                <td>{{ $conexao->porta_equipamento }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form action="{{ route('patch-panels.destroy', ['patchPanel' => $patchPanel]) }}" method="POST" class="mt-4 p-3 border border-danger rounded bg-light">
            @csrf
            @method('DELETE')
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirmo" value="1" id="confirmo" required>
                <label class="form-check-label" for="confirmo">
                    @if($totalVinculos)
                        Conferi a lista e quero excluir o patch panel <strong>{{ $patchPanel->nome }}</strong>
                        com seus <strong>{{ $totalVinculos }}</strong> vínculo(s).
                    @else
                        Quero excluir o patch panel <strong>{{ $patchPanel->nome }}</strong>.
                    @endif
                </label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger" id="btnExcluir" disabled>
                    <i class="fas fa-trash"></i> Excluir definitivamente
                </button>
                <a href="{{ route('racks.show', ['rack' => $patchPanel->rack]) }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('javascripts_bottom')
<script>
    document.getElementById('confirmo').addEventListener('change', function () {
        document.getElementById('btnExcluir').disabled = !this.checked;
    });
</script>
@endsection

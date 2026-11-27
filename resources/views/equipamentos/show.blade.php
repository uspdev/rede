@extends('main')

@section('content')
<div class="card">
    <div class="card-header bg-usp d-flex justify-content-between align-items-center">
        <span class="h4 mb-0 text-dark">
            <i class="fas fa-network-wired"></i> {{ $equipamento->hostname }}
        </span>
        <a href="#" onclick="history.back(); return false;" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Hostname:</strong> {{ $equipamento->hostname }}</li>
                    <li class="list-group-item"><strong>IP:</strong> {{ $equipamento->ip }}</li>
                    <li class="list-group-item"><strong>Modelo:</strong> {{ $equipamento->modeloSwitch?->nome ?? '-' }}</li>
                    <li class="list-group-item"><strong>Fabricante:</strong> {{ $equipamento->modeloSwitch?->fabricante ?? '-' }}</li>
                    <li class="list-group-item"><strong>Portas:</strong> {{ $equipamento->qtde_portas }}</li>
                    <li class="list-group-item"><strong>PoE:</strong> {{ $equipamento->modeloSwitch?->poe ? 'Sim' : 'Não' }}</li>
                    <li class="list-group-item"><strong>Rack:</strong> {{ $equipamento->rack->nome }} ({{ $equipamento->rack->predio->nome }})</li>
                    @if($equipamento->comentario)
                        <li class="list-group-item"><strong>Comentário:</strong> {{ $equipamento->comentario }}</li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Portas ({{ $equipamento->qtde_portas }})</h5>
                <span class="text-muted small">
                    {{ $conexoesPorPorta->count() }} conectada(s) a patch panel ·
                    <a href="{{ route('racks.show', ['rack' => $equipamento->rack]) }}">conectar pelo diagrama do rack</a>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th width="80px">Porta</th>
                                <th>Status</th>
                                <th>Patch Panel</th>
                                <th>Sala</th>
                                <th>Tipo de Porta</th>
                                <th width="140px">Ações</th>
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
                                        <a href="{{ route('patch-panels.show', ['patchPanel' => $conexao->patchPanel]) }}">{{ $conexao->patchPanel->nome }}</a>
                                        · porta <strong>{{ $conexao->porta_patch_panel }}</strong>
                                        @if($conexao->patchPanel->rack_id !== $equipamento->rack_id)
                                            <small class="text-muted">(rack {{ $conexao->patchPanel->rack->nome }})</small>
                                        @endif
                                    @else
                                        -
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
                                    @if($tipo)
                                        @if($tipo->cor)
                                            <span class="d-inline-block border rounded align-middle me-1" style="width: 16px; height: 16px; background-color: {{ $tipo->cor }};"></span>
                                        @endif
                                        <span class="align-middle">{{ $tipo->nome }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($conexao)
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-desconectar"
                                                data-url="{{ route('conexoes.destroy', ['conexao' => $conexao]) }}"
                                                data-desc="porta {{ $i }} ↔ {{ $conexao->patchPanel->nome }} porta {{ $conexao->porta_patch_panel }}">
                                            Desconectar
                                        </button>
                                    @else
                                        <span class="text-muted">—</span>
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
<script>
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

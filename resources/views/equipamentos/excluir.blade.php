@extends('main')

@section('content')
@php
    $totalHistorico = array_sum($historico);
@endphp

<br>

<div class="card border-danger">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <span class="h5 mb-0">
            <i class="fas fa-exclamation-triangle"></i> Excluir equipamento: {{ $equipamento->hostname }}
        </span>
        <a href="{{ route('racks.show', ['rack' => $equipamento->rack]) }}" class="btn btn-light btn-sm">
            <i class="fas fa-arrow-left"></i> Voltar sem excluir
        </a>
    </div>

    <div class="card-body">
        <p class="mb-1">
            <strong>IP:</strong> {{ $equipamento->ip ?: '—' }} ·
            <strong>Modelo:</strong> {{ $equipamento->modeloSwitch?->fabricante }} {{ $equipamento->modeloSwitch?->nome ?? '—' }} ·
            <strong>Rack:</strong> {{ $equipamento->rack->nome }} ({{ $equipamento->rack->predio->nome ?? '—' }})
        </p>

        @if($conexoes->isEmpty() && $totalHistorico === 0)
            <div class="alert alert-success mt-3 mb-4">
                Este equipamento não tem nenhuma porta conectada. Só o equipamento será excluído.
            </div>
        @else
            <div class="alert alert-warning mt-3 mb-4">
                <strong>Atenção:</strong> ao excluir este equipamento, também serão apagadas
                <strong>{{ $conexoes->count() }}</strong> conexão(ões) com patch panels
                @if($totalHistorico)
                    e o histórico de portas coletado pela API
                    (<strong>{{ $historico['portas'] }}</strong> porta(s),
                    <strong>{{ $historico['snapshots'] }}</strong> leitura(s),
                    <strong>{{ $historico['macs'] }}</strong> MAC(s))
                @endif.
                Os vínculos dos patch panels com as salas <strong>não</strong> são afetados.
                Confira a lista abaixo. <strong>Não é possível desfazer.</strong>
            </div>
        @endif

        @if($conexoes->isNotEmpty())
            <h2 class="h6 mt-4"><i class="fas fa-network-wired"></i> Conexões com patch panels ({{ $conexoes->count() }})</h2>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="130px">Porta do switch</th>
                            <th>Patch panel</th>
                            <th>Porta do patch panel</th>
                            <th>Sala</th>
                            <th>Tipo de porta</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conexoes as $conexao)
                            @php
                                $vinculo = $vinculos->get($conexao->patch_panel_id . ':' . $conexao->porta_patch_panel);
                                $tipo = $vinculo?->tipoPorta;
                            @endphp
                            <tr>
                                <td><strong>{{ $conexao->porta_equipamento }}</strong></td>
                                <td>
                                    {{ $conexao->patchPanel->nome ?? '—' }}
                                    @if($conexao->patchPanel?->rack && $conexao->patchPanel->rack_id !== $equipamento->rack_id)
                                        <small class="text-muted">(rack {{ $conexao->patchPanel->rack->nome }})</small>
                                    @endif
                                </td>
                                <td>{{ $conexao->porta_patch_panel }}</td>
                                <td>
                                    @if($vinculo?->sala)
                                        {{ $vinculo->sala->nome }}
                                        @if($vinculo->sala->predio)<small class="text-muted">({{ $vinculo->sala->predio->nome }})</small>@endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($tipo)
                                        @if($tipo->cor)
                                            <span class="d-inline-block border rounded align-middle me-1" style="width: 14px; height: 14px; background-color: {{ $tipo->cor }};"></span>
                                        @endif
                                        <span class="align-middle">{{ $tipo->nome }}</span>
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

        <form action="{{ route('equipamentos.destroy', ['equipamento' => $equipamento]) }}" method="POST" class="mt-4 p-3 border border-danger rounded bg-light">
            @csrf
            @method('DELETE')
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="confirmo" value="1" id="confirmo" required>
                <label class="form-check-label" for="confirmo">
                    @if($conexoes->isNotEmpty())
                        Conferi a lista e quero excluir o equipamento <strong>{{ $equipamento->hostname }}</strong>
                        com suas <strong>{{ $conexoes->count() }}</strong> conexão(ões).
                    @else
                        Quero excluir o equipamento <strong>{{ $equipamento->hostname }}</strong>.
                    @endif
                </label>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger" id="btnExcluir" disabled>
                    <i class="fas fa-trash"></i> Excluir definitivamente
                </button>
                <a href="{{ route('racks.show', ['rack' => $equipamento->rack]) }}" class="btn btn-secondary">Cancelar</a>
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

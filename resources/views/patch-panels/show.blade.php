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
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="100px">Porta</th>
                                <th>Status</th>
                                <th>Local Vinculado</th>
                                <th>Tipo de Porta</th>
                                <th>Switch</th>
                                <th>Comentário</th>
                                <th>Tamanho</th>
                                <th>Planta</th>
                                <th width="180px">Ações</th>
                            </tr>
                        </thead>
                        @php
                            $conexoesPorPorta = $patchPanel->conexoes()->with('equipamento')->get()->keyBy('porta_patch_panel');
                        @endphp
                        <tbody>
                            @foreach(range(1, $patchPanel->qtde_portas) as $porta)
                            @php
                                // Buscar a sala vinculada a esta porta
                                $vinculo = $salasVinculadas->first(function ($sala) use ($porta) {
                                    return $sala->pivot->porta == $porta;
                                });
                                
                                $tipoPorta = $vinculo ? $vinculo->pivot->tipoPorta : null;
                            @endphp
                            <tr>
                                <td><strong>{{ $porta }}</strong></td>
                                <td>
                                    @if($vinculo)
                                        <span class="badge bg-success">Vinculada</span>
                                    @else
                                        <span class="badge bg-secondary">Livre</span>
                                    @endif
                                </td>
                                <td>
                                    @if($vinculo)
                                        {{ $vinculo->nome }} ({{ $vinculo->predio->nome }})
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($tipoPorta)
                                        @if($tipoPorta->cor)
                                            <span class="d-inline-block border rounded align-middle me-1" style="width: 18px; height: 18px; background-color: {{ $tipoPorta->cor }};" title="{{ $tipoPorta->cor }}"></span>
                                        @endif
                                        <span class="align-middle">{{ $tipoPorta->nome }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @php $conexao = $conexoesPorPorta->get($porta); @endphp
                                    @if($conexao)
                                        <i class="fas fa-link text-success"></i>
                                        {{ $conexao->equipamento?->hostname }} · porta {{ $conexao->porta_equipamento }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($vinculo && $vinculo->pivot->comentario)
                                        {{ $vinculo->pivot->comentario }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($vinculo && $vinculo->pivot->tamanho)
                                        {{ $vinculo->pivot->tamanho }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($vinculo && $vinculo->pivot->planta_id)
                                        <a href="{{ route('plantas.edit', ['planta' => $vinculo->pivot->planta_id]) }}" class="btn btn-sm btn-info">
                                            Ver Planta
                                        </a>
                                    @else
                                        -
                                    @endif
                                <td>
                                    @can('user')
                                    @if($vinculo)
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('patch-panels.editar-tipo-porta', ['patchPanel' => $patchPanel, 'sala' => $vinculo, 'porta' => $porta]) }}"
                                            class="btn btn-warning btn-sm">
                                                Editar
                                            </a>
                                            <form action="{{ route('patch-panels.desvincular-sala', ['patchPanel' => $patchPanel, 'sala' => $vinculo, 'porta' => $porta]) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja desvincular esta porta?')">
                                                    Desvincular
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <a href="{{ route('patch-panels.selecionar-sala', ['patchPanel' => $patchPanel, 'porta' => $porta]) }}" class="btn btn-primary btn-sm">
                                            Vincular
                                        </a>
                                    @endif
                                    @endcan
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

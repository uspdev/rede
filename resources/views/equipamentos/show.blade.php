@extends('main')

@section('content')

<div class="card">
    <div class="card-header bg-usp">
        <div class="d-flex justify-content-between align-items-center">
            <span class="h4 mb-0 text-dark">
                <i class="fas fa-network-wired"></i> Equipamento: {{ $equipamento->hostname }}
                <small class="text-muted">
                    Rack: {{ $equipamento->rack->nome }} | Prédio: {{ $equipamento->rack->predio->nome }}
                </small>
            </span>
            <div>
                <a href="/racks/{{ $equipamento->rack_id }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Informações do Equipamento</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><strong>Hostname:</strong> {{ $equipamento->hostname }}</li>
                            <li class="list-group-item"><strong>Modelo:</strong> {{ $equipamento->model }}</li>
                            <li class="list-group-item"><strong>IP:</strong> {{ $equipamento->ip }}</li>
                            <li class="list-group-item"><strong>Quantidade de Portas:</strong> {{ $equipamento->qtde_portas }}</li>
                            <li class="list-group-item">
                                <strong>PoE:</strong> 
                                @if($equipamento->poe_type)
                                    <span class="badge bg-success">Sim</span>
                                @else
                                    <span class="badge bg-secondary">Não</span>
                                @endif
                            </li>
                            <li class="list-group-item"><strong>Prédio:</strong> {{ $equipamento->rack->predio->nome }}</li>
                            <li class="list-group-item"><strong>Rack:</strong> {{ $equipamento->rack->nome }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Portas do Equipamento ({{ $equipamento->qtde_portas }})</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="100px">Porta</th>
                                <th>Status</th>
                                <th width="180px">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 1; $i <= $equipamento->qtde_portas; $i++)
                            <tr>
                                <td><strong>{{ $i }}</strong></td>
                                <td>
                                    <span class="badge bg-secondary">Livre</span>
                                </td>
                                <td>
                                    <span class="text-muted">Botão vinculo</span>
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

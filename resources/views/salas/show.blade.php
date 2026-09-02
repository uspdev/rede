@extends('main')

@section('content')

@can('user')
@include('partials.search') 
@endcan
<br>

<div class="card">
    <div class="card-header bg-usp">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0 text-dark">
                <i class="fas fa-door-open"></i> Local: {{ $sala->nome }}
                <small class="text-muted d-block">{{ $sala->predio->nome }}</small>
            </h1>
            <a href="{{ route('predios.show', ['predio' => $sala->predio]) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Portas Vinculadas</h3>
            @can('user')
            <a href="{{ route('salas.selecionar-rack', ['sala' => $sala]) }}" class="btn btn-success">
                <i class="fas fa-link"></i> Vincular a uma Porta
            </a>
            @endcan
        </div>
        
        @if($patchPanels->isEmpty())
            <div class="alert alert-info">Nenhuma porta vinculada a esta sala.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Patch Panel</th>
                            <th>Rack</th>
                            <th>Prédio</th>
                            <th>Porta</th>
                            <th>Tipo de Porta</th> 
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($patchPanels as $pp)
                        @php
                            $tipoPorta = $pp->pivot->tipoPorta; 
                        @endphp
                        <tr>
                            <td>{{ $pp->nome }}</td>
                            <td>{{ $pp->rack->nome }}</td>
                            <td>{{ $pp->rack->predio->nome }}</td>
                            <td>{{ $pp->pivot->porta }}</td>
                            <td>
                                @if($tipoPorta)
                                    {{ $tipoPorta->nome }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @can('user')
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('salas.editar-tipo-porta', ['sala' => $sala, 'patchPanel' => $pp, 'porta' => $pp->pivot->porta]) }}"
                                        class="btn btn-warning btn-sm">
                                            Editar tipo de porta
                                        </a>
                                        <form action="{{ route('salas.desvincular-patchpanel', ['sala' => $sala, 'patchPanel' => $pp, 'porta' => $pp->pivot->porta]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja desvincular esta porta?')">
                                                Desvincular
                                            </button>
                                        </form>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center">
                {{ $patchPanels->links() }}
            </div>
        @endif
    </div>
</div>

@endsection

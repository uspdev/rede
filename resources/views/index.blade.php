@extends('main')

@section('content')

@forelse($predios as $predio)
    <h4 class="mt-4 text-uppercase">Prédio: {{ $predio->nome }}</h4>

    @forelse($predio->telecomRooms as $tr)
    <h6 class="mt-2 text-muted"><i class="fas fa-door-closed"></i> Telecommunications Room: {{ $tr->nome }}</h6>
    <div class="row">
        @forelse($tr->racks as $rack)
            <div class="col-md-3 mb-3">
                <div class="card h-100">
                    <div class="card-header bg-usp py-2 d-flex justify-content-between align-items-center">
                        @can('admin')
                            <a href="{{ route('racks.show', ['rack' => $rack]) }}" class="text-dark" title="Gerenciar o rack {{ $rack->nome }}">
                                <strong>{{ $rack->nome }}</strong>
                            </a>
                            <a href="{{ route('racks.show', ['rack' => $rack]) }}" class="btn btn-light btn-sm py-0 px-2" title="Gerenciar o rack {{ $rack->nome }}">
                                <i class="fas fa-cog"></i> Gerenciar
                            </a>
                        @else
                            <strong class="text-dark">{{ $rack->nome }}</strong>
                        @endcan
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse($rack->equipamentos as $equipamento)
                            <li class="list-group-item py-1">
                                @can('admin')
                                    <a href="{{ route('equipamentos.show', ['equipamento' => $equipamento]) }}">
                                        {{ $equipamento->hostname }}
                                    </a>
                                @else
                                    {{ $equipamento->hostname }}
                                @endcan
                            </li>
                        @empty
                            <li class="list-group-item py-1 text-muted">Sem switches</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">Nenhum rack nesta Telecommunications Room.</div>
            </div>
        @endforelse
    </div>
    @empty
        <div class="alert alert-info">Nenhuma Telecommunications Room cadastrada neste prédio.</div>
    @endforelse
@empty
    <div class="alert alert-info">Nenhum prédio cadastrado ainda.</div>
@endforelse

@endsection('content')
@extends('main')

@section('content')

<div class="card">
    <div class="card-header bg-usp">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0 text-dark">
                <i class="fas fa-building"></i> {{ $predio->nome }}
            </h1>
            <a href="{{ route('home') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="card-body">
        @if($predio->descricao)
        <p><strong>Descrição:</strong> {{ $predio->descricao }}</p>
        @endif

        <div class="row mt-4">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Locais e Salas</h2>
                        @can('user')
                        <div>
                            <a href="{{ route('salas.create', ['predio_id' => $predio->id]) }}" class="btn btn-success btn-sm ml-2">
                                <i class="fas fa-plus"></i> Novo Local/Sala
                            </a>
                        </div>
                        @endcan
                    </div>
                    <div class="card-body">
                        @if($salas->isEmpty())
                            <div class="alert alert-info">Nenhum local ou sala cadastrados.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Descrição</th>
                                            <th width="220px">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($salas as $sala)
                                        <tr>
                                            <td>{{ $sala->nome }}</td>
                                            <td>{{ $sala->descricao }}</td>
                                            <td>
                                                <a href="{{ route('salas.show', ['sala' => $sala]) }}" class="btn btn-info btn-sm">Ver</a>
                                                <a href="{{ route('salas.edit', ['sala' => $sala]) }}" class="btn btn-warning btn-sm">Editar</a>
                                                <form action="{{ route('salas.destroy', ['sala' => $sala]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este local?')">
                                                        Excluir
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Racks</h2>
                        @can('user')
                        <div>
                            <a href="{{ route('racks.create', ['predio_id' => $predio->id]) }}" class="btn btn-success btn-sm ml-2">
                                <i class="fas fa-plus"></i> Novo Rack
                            </a>
                        </div>
                        @endcan
                    </div>
                    <div class="card-body">
                        @if($racks->isEmpty())
                            <div class="alert alert-info">Nenhum rack cadastrado.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th width="220px">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($racks as $rack)
                                        <tr>
                                            <td>{{ $rack->nome }}</td>
                                            <td>
                                                <a href="{{ route('racks.show', ['rack' => $rack]) }}" class="btn btn-info btn-sm">Ver</a>
                                                <a href="{{ route('racks.edit', ['rack' => $rack]) }}" class="btn btn-warning btn-sm">Editar</a>
                                                <form action="{{ route('racks.destroy', ['rack' => $rack]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este rack?')">
                                                        Excluir
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <a href="{{ route('plantas.index', ['predio' => $predio]) }}" class="btn btn-primary mb-3">Ver Plantas</a>
</div>
@endsection

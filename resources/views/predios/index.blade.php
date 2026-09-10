@extends('main')

@section('content')

<br>
<div class="card">
    <div class="card-header bg-usp">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h4 mb-0 text-dark"> 
                    <i class="fas fa-building"></i> Prédios 
                </h1>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            @can('user')
            <a href="{{ route('predios.create') }}" class="btn btn-success ml-2">
                <i class="fas fa-plus"></i> Adicionar Novo Prédio
            </a>
            @endcan
        </div>
        
        @if($predios->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-building"></i> Nenhum prédio cadastrado ainda.
        </div>
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
                    @foreach($predios as $predio)
                    <tr>
                        <td>{{ $predio->nome }}</td>
                        <td>{{ $predio->descricao ?? '-' }}</td>
                        <td>
                            <a href="{{ route('predios.show', ['predio' => $predio]) }}" class="btn btn-info btn-sm">Ver</a>
                            <a href="{{ route('predios.edit', ['predio' => $predio]) }}" class="btn btn-warning btn-sm">Editar</a>
                            <form action="{{ route('predios.destroy', ['predio' => $predio]) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este prédio?')">
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
@endsection

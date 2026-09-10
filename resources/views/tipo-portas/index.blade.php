@extends('main')

@section('content')
<div class="card">
    <div class="card-header bg-usp">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0 text-dark">
                <i class="fas fa-plug"></i> Tipos de Porta
            </h1>
            <a href="{{ route('tipo-portas.create') }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Novo Tipo
            </a>
        </div>
    </div>
    <div class="card-body">
        @if($tipoPortas->isEmpty())
            <div class="alert alert-info">Nenhum tipo de porta cadastrado.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th width="150px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tipoPortas as $tipoPorta)
                        <tr>
                            <td>{{ $tipoPorta->nome }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('tipo-portas.edit', ['tipoPorta' => $tipoPorta]) }}" class="btn btn-warning btn-sm">Editar</a>
                                    <form action="{{ route('tipo-portas.destroy', ['tipoPorta' => $tipoPorta]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este tipo de porta?')">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
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

@extends('main')

@section('content')
<div class="card">
    <div class="card-header">
        <h1>Editar Prédio: {{ $predio->nome }}</h1>
    </div>
    <div class="card-body">
        <form action="{{ route('predios.update', ['predio' => $predio]) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-3">
                <label for="nome" class="form-label">Nome do Prédio *</label>
                <input type="text" class="form-control" id="nome" name="nome" value="{{ $predio->nome }}">
            </div>
            
            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3">{{ $predio->descricao }}</textarea>
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-success">Atualizar</button>
                <a href="{{ route('home') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

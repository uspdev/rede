@extends('main')

@section('content')
<div class="card">
    <div class="card-header">
        <h1>Cadastrar Novo Tipo de Porta de Patch Panel</h1>
    </div>
    <div class="card-body">
        <form action="{{ route('tipo-portas.store') }}" method="POST">
            @csrf
            
            {{-- Campo Nome --}}
            <div class="mb-3">
                <label for="nome" class="form-label">Nome *</label>
                <input type="text" 
                       class="form-control" 
                       id="nome" 
                       name="nome" 
                       value="{{ old('nome') }}" 
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Cor</label>
                @include('partials.cor-picker', ['name' => 'cor', 'valor' => old('cor', '#FF0000'), 'ajuda' => 'Cor utilizada para identificar o marcador no mapa.'])
            </div>
            
            {{-- Ações --}}
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-success">Salvar</button>
                <a href="{{ route('tipo-portas.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

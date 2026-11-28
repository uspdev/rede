@extends('main')

@section('content')
<div class="card">
    <div class="card-header">
        <h1>Editar Tipo de Porta de Patch Panel: {{ $tipoPorta->nome }}</h1>
    </div>
    <div class="card-body">
        <form action="{{ route('tipo-portas.update', ['tipoPorta' => $tipoPorta]) }}" method="POST">
            @csrf
            @method('PUT')
            
            {{-- Campo Nome --}}
            <div class="mb-3">
                <label for="nome" class="form-label">Nome *</label>
                <input type="text" 
                       class="form-control" 
                       id="nome" 
                       name="nome" 
                       value="{{ old('nome', $tipoPorta->nome) }}" 
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Cor</label>
                @include('partials.cor-picker', ['name' => 'cor', 'valor' => old('cor', $tipoPorta->cor ?? '#FF0000'), 'ajuda' => 'Cor utilizada para identificar o marcador no mapa.'])
            </div>
            
            {{-- Ações --}}
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">Atualizar</button>
                <a href="{{ route('tipo-portas.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

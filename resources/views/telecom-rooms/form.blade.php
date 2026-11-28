@extends('main')

@section('content')
@php $editando = $tr->exists; @endphp
<div class="card">
    <div class="card-header">
        <h1 class="h4 mb-0">
            {{ $editando ? 'Editar ' . $tr->nome : 'Nova Telecommunications Room' }}
            <small class="text-muted">· {{ $predio->nome }}</small>
        </h1>
    </div>
    <div class="card-body">
        <p class="text-muted small">A Telecommunications Room é a sala técnica do prédio onde ficam os racks.</p>
        <form action="{{ $editando ? route('telecom-rooms.update', ['telecomRoom' => $tr]) : route('telecom-rooms.store') }}" method="POST">
            @csrf
            @if($editando)
                @method('PUT')
            @else
                <input type="hidden" name="predio_id" value="{{ $predio->id }}">
            @endif

            <div class="mb-3">
                <label for="nome" class="form-label">Nome *</label>
                <input type="text" class="form-control" id="nome" name="nome" required
                       value="{{ old('nome', $tr->nome ?? $sugestao ?? '') }}" placeholder="Ex.: TR 1, TR Térreo">
            </div>

            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição / localização</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="3" placeholder="Ex.: 2º andar, ao lado do elevador">{{ old('descricao', $tr->descricao) }}</textarea>
            </div>

            <div class="d-flex justify-content-between">
                <button type="submit" class="btn {{ $editando ? 'btn-primary' : 'btn-success' }}">{{ $editando ? 'Atualizar' : 'Salvar' }}</button>
                <a href="{{ $editando ? route('telecom-rooms.show', ['telecomRoom' => $tr]) : route('predios.trs', ['predio' => $predio]) }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

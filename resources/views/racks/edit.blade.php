@extends('main')

@section('content')
<div class="card">
    <div class="card-header">
        <h1>Editar Rack: {{ $rack->nome }}</h1>
    </div>
    <div class="card-body">
        <form action="{{ route('racks.update', ['rack' => $rack]) }}" method="POST">
            @csrf
            @method('PUT')

            @include('racks.partials.select-tr', ['selecionada' => $rack->telecom_room_id])
            
            <div class="mb-3">
                <label for="nome" class="form-label">Identificação do Rack *</label>
                <input type="text" class="form-control" id="nome" name="nome" value="{{ old('nome', $rack->nome) }}">
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">Atualizar</button>
                <a href="{{ route('racks.show', ['rack' => $rack]) }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

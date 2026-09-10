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
            
            <div class="mb-3">
                <label for="predio_id" class="form-label">Prédio *</label>
                <select class="form-select" id="predio_id" name="predio_id" required>
                    @foreach($predios as $predio)
                        <option value="{{ $predio->id }}" {{ $rack->predio_id == $predio->id ? 'selected' : '' }}>
                            {{ $predio->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-3">
                <label for="nome" class="form-label">Identificação do Rack *</label>
                <input type="text" class="form-control" id="nome" name="nome" value="{{ $rack->nome }}">
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">Atualizar</button>
                <a href="{{ url()->previous() }}" class="btn btn-success">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

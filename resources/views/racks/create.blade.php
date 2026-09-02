@extends('main')

@section('content')
<div class="card">
    <div class="card-header">
        <h1>Cadastrar Novo Rack</h1>
    </div>
    <div class="card-body">
        <form action="{{ route('racks.store') }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label for="predio_id" class="form-label">Prédio *</label>
                <select class="form-select" id="predio_id" name="predio_id" required>
                    <option value="">Selecione um prédio</option>
                    @foreach($predios as $predio)
                        <option value="{{ $predio->id }}" 
                            {{ (old('predio_id') ?? $predio_selecionado ?? '') == $predio->id ? 'selected' : '' }}>
                            {{ $predio->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-3">
                <label for="nome" class="form-label">Identificação do Rack *</label>
                <input type="text" class="form-control" id="nome" name="nome" value="{{ old('nome') }}">
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-success">Salvar</button>
                <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

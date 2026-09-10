@extends('main')

@section('content')
<div class="card">
    <div class="card-header">
        <h1>Cadastrar Novo Patch Panel</h1>
    </div>
    <div class="card-body">
        <form action="{{ route('patch-panels.store') }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label for="rack_id" class="form-label">Rack *</label>
                <select class="form-select" id="rack_id" name="rack_id" required>
                    <option value="">Selecione um rack</option>
                    @foreach($racks as $rack)
                        <option value="{{ $rack->id }}" 
                            {{ (old('rack_id') ?? $rack_selecionado ?? '') == $rack->id ? 'selected' : '' }}>
                            {{ $rack->nome }} ({{ $rack->predio->nome }})
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-3">
                <label for="nome" class="form-label">Identificação *</label>
                <input type="text" class="form-control" id="nome" name="nome">
            </div>
            
            <div class="mb-3">
                <label for="qtde_portas" class="form-label">Quantidade de Portas *</label>
                <input type="number" class="form-control" id="qtde_portas" name="qtde_portas">
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-success">Salvar</button>
                <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

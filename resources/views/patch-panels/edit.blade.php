@extends('main')

@section('content')
<div class="card">
    <div class="card-header">
        <h1>Editar Patch Panel: {{ $patchPanel->nome }}</h1>
    </div>
    <div class="card-body">
        <form action="{{ route('patch-panels.update', ['patchPanel' => $patchPanel]) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-3">
                <label for="rack_id" class="form-label">Rack *</label>
                <select class="form-select" id="rack_id" name="rack_id" required>
                    @foreach($racks as $rack)
                        <option value="{{ $rack->id }}" {{ $patchPanel->rack_id == $rack->id ? 'selected' : '' }}>
                            {{ $rack->nome }} ({{ $rack->predio->nome }})
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-3">
                <label for="nome" class="form-label">Identificação *</label>
                <input type="text" class="form-control" id="nome" name="nome" value="{{ $patchPanel->nome }}">
            </div>
            
            <div class="mb-3">
                <label for="qtde_portas" class="form-label">Quantidade de Portas *</label>
                <input type="number" class="form-control" id="qtde_portas" name="qtde_portas" min="1" value="{{ $patchPanel->qtde_portas }}">
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-success">Atualizar</button>
                <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

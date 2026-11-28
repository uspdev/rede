@extends('main')

@section('content')
<div class="card">
    <div class="card-header">
        <h1>Cadastrar Novo Rack</h1>
    </div>
    <div class="card-body">
        <form action="{{ route('racks.store') }}" method="POST">
            @csrf

            @include('racks.partials.select-tr', ['selecionada' => $tr_selecionada])
            
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

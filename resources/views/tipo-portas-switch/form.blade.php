@extends('main')

@section('content')
@php $editando = $tipo->exists; @endphp
<div class="card">
    <div class="card-header">
        <h1>{{ $editando ? 'Editar Tipo de Porta de Switch: ' . $tipo->nome : 'Cadastrar Novo Tipo de Porta de Switch' }}</h1>
    </div>
    <div class="card-body">
        <form action="{{ $editando ? route('tipo-portas-switch.update', ['tipoPortaSwitch' => $tipo]) : route('tipo-portas-switch.store') }}" method="POST">
            @csrf
            @if($editando) @method('PUT') @endif

            <div class="mb-3">
                <label for="nome" class="form-label">Nome *</label>
                <input type="text" class="form-control" id="nome" name="nome"
                       value="{{ old('nome', $tipo->nome) }}" placeholder="Ex.: Acesso, Uplink, Câmeras" required>
            </div>

            <div class="mb-3">
                <label for="vlan" class="form-label">VLAN</label>
                <input type="number" class="form-control" id="vlan" name="vlan" min="1" max="4094"
                       value="{{ old('vlan', $tipo->vlan) }}" placeholder="Opcional (1 a 4094)">
            </div>

            <div class="mb-3">
                <label class="form-label">Cor</label>
                @include('partials.cor-picker', ['name' => 'cor', 'valor' => old('cor', $tipo->cor ?? '#0D6EFD'), 'ajuda' => 'Cor usada para identificar a porta no desenho do switch.'])
            </div>

            <div class="d-flex justify-content-between">
                <button type="submit" class="btn {{ $editando ? 'btn-primary' : 'btn-success' }}">{{ $editando ? 'Atualizar' : 'Salvar' }}</button>
                <a href="{{ route('tipo-portas-switch.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection

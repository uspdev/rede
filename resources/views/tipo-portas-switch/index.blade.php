@extends('main')

@section('content')
<div class="card">
    <div class="card-header bg-usp">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0 text-dark">
                <i class="fas fa-ethernet"></i> Tipos de Porta de Switch
            </h1>
            <a href="{{ route('tipo-portas-switch.create') }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Novo Tipo
            </a>
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Tipos atribuídos às portas dos switches (ex.: Acesso, Uplink, Câmeras).
            Para os tipos das portas de patch panel, veja <a href="{{ route('tipo-portas.index') }}">Tipos de Porta de Patch Panel</a>.
        </p>
        @if($tipos->isEmpty())
            <div class="alert alert-info">Nenhum tipo de porta de switch cadastrado.</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Cor</th>
                            <th>VLAN</th>
                            <th>Portas</th>
                            <th width="150px">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tipos as $tipo)
                        <tr>
                            <td>{{ $tipo->nome }}</td>
                            <td>
                                @if($tipo->cor)
                                    <span class="d-inline-block border rounded align-middle me-1" style="width: 24px; height: 24px; background-color: {{ $tipo->cor }};" title="{{ $tipo->cor }}"></span>
                                    <code class="align-middle">{{ $tipo->cor }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $tipo->vlan ?? '—' }}</td>
                            <td>{{ $tipo->portas_count }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('tipo-portas-switch.edit', ['tipoPortaSwitch' => $tipo]) }}" class="btn btn-warning btn-sm">Editar</a>
                                    <form action="{{ route('tipo-portas-switch.destroy', ['tipoPortaSwitch' => $tipo]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este tipo de porta de switch?')">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

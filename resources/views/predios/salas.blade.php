@extends('main')

@section('content')
<style>
    .secao-titulo { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: .75rem; }
    .secao-titulo h2 { font-size: 1.1rem; margin: 0; }
    .secao-titulo h2 .badge { font-size: .7rem; vertical-align: middle; }
    .tr-card { border-left: 4px solid #1094ab; }
    .tr-card .tr-nome { font-weight: 600; color: inherit; text-decoration: none; }
    .tr-card .tr-nome:hover { text-decoration: underline; }
    .rack-item { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 6px 10px; border-radius: 6px; background: #f5f7f9; text-decoration: none; color: inherit; }
    .rack-item:hover { background: #e8f4f7; }
    .rack-item small { color: #6c757d; white-space: nowrap; }
    .planta-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border: 1px solid #e3e6ea; border-radius: 8px; }
</style>

@include('predios.partials.cabecalho', ['aba' => 'salas'])

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="secao-titulo">
                    <h2><i class="fas fa-door-open text-secondary"></i> Salas/Ambientes <span class="badge bg-secondary">{{ $salas->count() }}</span></h2>
                    <div class="d-flex gap-2">
                        @if($salas->count() > 8)
                            <input type="search" id="sala-busca" class="form-control form-control-sm" style="max-width: 220px;" placeholder="Buscar sala/ambiente…">
                        @endif
                        @can('user')
                            <a href="{{ route('salas.create', ['predio_id' => $predio->id]) }}" class="btn btn-success btn-sm text-nowrap"><i class="fas fa-plus"></i> Nova Sala/Ambiente</a>
                        @endcan
                    </div>
                </div>
                <p class="text-muted small mb-3">Salas, corredores, saguões e outros locais onde há pontos de rede. Também é possível criar direto na planta, em "Marcar Salas/Ambientes".</p>
                @if($salas->isEmpty())
                    <div class="alert alert-info mb-0">Nenhuma sala/ambiente cadastrada.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Descrição</th>
                                    <th width="200px">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salas as $sala)
                                <tr class="sala-linha" data-busca="{{ Str::lower($sala->nome . ' ' . $sala->descricao) }}">
                                    <td>{{ $sala->nome }}</td>
                                    <td class="text-muted">{{ $sala->descricao }}</td>
                                    <td>
                                        <a href="{{ route('salas.show', ['sala' => $sala]) }}" class="btn btn-info btn-sm">Ver</a>
                                        <a href="{{ route('salas.edit', ['sala' => $sala]) }}" class="btn btn-warning btn-sm">Editar</a>
                                        <form action="{{ route('salas.destroy', ['sala' => $sala]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir esta sala/ambiente?')">Excluir</button>
                                        </form>
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

@section('javascripts_bottom')
<script>
(function () {
    const busca = document.getElementById('sala-busca');
    if (!busca) return;
    busca.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('.sala-linha').forEach(l => l.style.display = !q || l.dataset.busca.includes(q) ? '' : 'none');
    });
})();
</script>
@endsection

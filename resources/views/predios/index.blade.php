@extends('main')

@section('content')
<style>
    .predio-card { transition: box-shadow .15s, transform .15s; border-top: 4px solid #1094ab; }
    .predio-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1.25rem rgba(0,0,0,.12) !important; }
    .predio-card .predio-nome { font-size: 1.1rem; font-weight: 600; }
    .predio-card .predio-nome a { color: inherit; text-decoration: none; }
    .predio-card .predio-nome a:hover { text-decoration: underline; }
    .predio-card .predio-desc { color: #6c757d; font-size: .875rem; min-height: 1.3em; }
    .predio-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    .predio-stat { background: #f5f7f9; border-radius: 8px; padding: 8px 4px; text-align: center; text-decoration: none; color: inherit; transition: background .12s, box-shadow .12s; }
    .predio-stat:hover { background: #e3f3f6; box-shadow: inset 0 0 0 1px #1094ab; color: inherit; }
    .predio-stat strong { display: block; font-size: 1.2rem; line-height: 1.1; }
    .predio-stat span { font-size: .72rem; color: #6c757d; text-transform: uppercase; letter-spacing: .03em; }
    .predio-stat i { color: #1094ab; font-size: .8rem; }
    .predio-card .card-footer { background: transparent; }
    .predio-busca { max-width: 280px; }
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h1 class="h4 mb-0"><i class="fas fa-building"></i> Prédios</h1>
    <div class="d-flex gap-2 align-items-center">
        @if($predios->count() > 6)
            <input type="search" id="predio-busca" class="form-control form-control-sm predio-busca" placeholder="Buscar prédio…">
        @endif
        @can('user')
            <a href="{{ route('predios.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Novo Prédio
            </a>
        @endcan
    </div>
</div>

@if($predios->isEmpty())
    <div class="alert alert-info">
        <i class="fas fa-building"></i> Nenhum prédio cadastrado ainda.
    </div>
@else
    <div class="row g-3" id="predios-grid">
        @foreach($predios as $predio)
            <div class="col-sm-6 col-lg-4 col-xxl-3 predio-col" data-busca="{{ Str::lower($predio->nome . ' ' . $predio->descricao) }}">
                <div class="card h-100 shadow-sm predio-card">
                    <div class="card-body">
                        <div class="predio-nome mb-1">
                            <i class="fas fa-building text-secondary me-1"></i>
                            <a href="{{ route('predios.show', ['predio' => $predio]) }}">{{ $predio->nome }}</a>
                        </div>
                        <div class="predio-desc mb-3">{{ Str::limit($predio->descricao, 90) }}</div>
                        <div class="predio-stats">
                            <a href="{{ route('predios.trs', ['predio' => $predio]) }}" class="predio-stat" title="Telecommunications Rooms">
                                <i class="fas fa-door-closed"></i><strong>{{ $predio->telecom_rooms_count }}</strong><span>Telecommunications Rooms</span>
                            </a>
                            <a href="{{ route('predios.salas', ['predio' => $predio]) }}" class="predio-stat" title="Salas/Ambientes">
                                <i class="fas fa-door-open"></i><strong>{{ $predio->salas_count }}</strong><span>Salas/Ambientes</span>
                            </a>
                            <a href="{{ route('plantas.index', ['predio' => $predio]) }}" class="predio-stat" title="Plantas baixas">
                                <i class="fas fa-map"></i><strong>{{ $predio->plantas_count }}</strong><span>Plantas</span>
                            </a>
                        </div>
                    </div>
                    <div class="card-footer border-0 pt-0 d-flex justify-content-end gap-1">
                        <a href="{{ route('predios.edit', ['predio' => $predio]) }}" class="btn btn-outline-secondary btn-sm" title="Editar"><i class="fas fa-pen"></i></a>
                        <form action="{{ route('predios.destroy', ['predio' => $predio]) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir o prédio {{ $predio->nome }}?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection

@section('javascripts_bottom')
<script>
(function () {
    const busca = document.getElementById('predio-busca');
    if (!busca) return;
    busca.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('.predio-col').forEach(c => c.style.display = !q || c.dataset.busca.includes(q) ? '' : 'none');
    });
})();
</script>
@endsection

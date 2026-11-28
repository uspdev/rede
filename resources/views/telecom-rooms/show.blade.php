@extends('main')

@section('content')
<div class="card">
    <div class="card-header bg-usp d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-0 text-dark"><i class="fas fa-door-closed"></i> {{ $tr->nome }}</h1>
            <small class="text-muted">
                Telecommunications Room · <a href="{{ route('predios.show', ['predio' => $tr->predio]) }}">{{ $tr->predio->nome }}</a>
            </small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('telecom-rooms.edit', ['telecomRoom' => $tr]) }}" class="btn btn-warning btn-sm"><i class="fas fa-pen"></i> Editar</a>
            <form action="{{ route('telecom-rooms.destroy', ['telecomRoom' => $tr]) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Excluir {{ $tr->nome }}?')"><i class="fas fa-trash"></i> Excluir</button>
            </form>
            <a href="{{ route('predios.trs', ['predio' => $tr->predio]) }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>
    </div>
    <div class="card-body">
        @if($tr->descricao)
            <p class="mb-3">{{ $tr->descricao }}</p>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Racks ({{ $racks->count() }})</h2>
            <a href="{{ route('racks.create', ['telecom_room_id' => $tr->id]) }}" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Novo Rack</a>
        </div>

        @if($racks->isEmpty())
            <div class="alert alert-info mb-0">Nenhum rack nesta Telecommunications Room.</div>
        @else
            <div class="row g-3">
                @foreach($racks as $rack)
                    <div class="col-sm-6 col-lg-4">
                        <a href="{{ route('racks.show', ['rack' => $rack]) }}" class="card h-100 text-decoration-none text-reset shadow-sm rack-card">
                            <div class="card-body">
                                <div class="fw-semibold"><i class="fas fa-server text-secondary me-1"></i> {{ $rack->nome }}</div>
                                <small class="text-muted">
                                    {{ $rack->equipamentos_count }} switch(es) · {{ $rack->patch_panels_count }} patch panel(s)
                                </small>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
<style>.rack-card { transition: box-shadow .15s, transform .15s; } .rack-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.12) !important; }</style>
@endsection

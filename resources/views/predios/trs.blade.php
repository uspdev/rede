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

@include('predios.partials.cabecalho', ['aba' => 'trs'])

        <div class="card shadow-sm ">
            <div class="card-body">
                <div class="secao-titulo">
                    <h2><i class="fas fa-door-closed text-secondary"></i> Telecommunications Rooms <span class="badge bg-secondary">{{ $trs->count() }}</span></h2>
                    @can('user')
                        <a href="{{ route('telecom-rooms.create', ['predio_id' => $predio->id]) }}" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Nova Telecommunications Room</a>
                    @endcan
                </div>

                @if($trs->isEmpty() && $racksSemTr->isEmpty())
                    <div class="alert alert-info mb-0">Nenhuma Telecommunications Room cadastrada. Crie uma Telecommunications Room para cadastrar os racks.</div>
                @endif

                <div class="d-flex flex-column gap-3">
                    @foreach($trs as $tr)
                        <div class="card tr-card">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                                    <div>
                                        <a href="{{ route('telecom-rooms.show', ['telecomRoom' => $tr]) }}" class="tr-nome">{{ $tr->nome }}</a>
                                        @if($tr->descricao)<small class="text-muted"> · {{ Str::limit($tr->descricao, 60) }}</small>@endif
                                    </div>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('racks.create', ['telecom_room_id' => $tr->id]) }}" class="btn btn-outline-success btn-sm" title="Novo rack nesta Telecommunications Room"><i class="fas fa-plus"></i> Rack</a>
                                        <a href="{{ route('telecom-rooms.edit', ['telecomRoom' => $tr]) }}" class="btn btn-outline-secondary btn-sm" title="Editar Telecommunications Room"><i class="fas fa-pen"></i></a>
                                    </div>
                                </div>
                                @if($tr->racks->isEmpty())
                                    <div class="text-muted small mb-1">Nenhum rack nesta Telecommunications Room.</div>
                                @else
                                    <div class="row g-2">
                                        @foreach($tr->racks as $rack)
                                            <div class="col-md-6">
                                                <a href="{{ route('racks.show', ['rack' => $rack]) }}" class="rack-item">
                                                    <span><i class="fas fa-server text-secondary me-1"></i> {{ $rack->nome }}</span>
                                                    <small>{{ $rack->equipamentos_count }} sw · {{ $rack->patch_panels_count }} pp</small>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @if($racksSemTr->isNotEmpty())
                        <div class="card border-warning">
                            <div class="card-body py-2">
                                <div class="fw-semibold text-warning-emphasis mb-2"><i class="fas fa-exclamation-triangle"></i> Racks sem Telecommunications Room</div>
                                <div class="row g-2">
                                    @foreach($racksSemTr as $rack)
                                        <div class="col-md-6">
                                            <a href="{{ route('racks.edit', ['rack' => $rack]) }}" class="rack-item" title="Editar para escolher a Telecommunications Room">
                                                <span><i class="fas fa-server text-secondary me-1"></i> {{ $rack->nome }}</span>
                                                <small>definir Telecommunications Room</small>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
@endsection

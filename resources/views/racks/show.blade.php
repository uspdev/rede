@extends('main')

@section('content')

<br>

<div class="card">
    <div class="card-header bg-usp">
        <div class="d-flex justify-content-between align-items-center">
            <span class="h4 mb-0 text-dark">
                <i class="fas fa-server"></i> Rack: {{ $rack->nome }}
                <small class="text-muted">
                    @if($rack->telecomRoom){{ $rack->telecomRoom->nome }} · @endif{{ $rack->predio->nome }}
                </small>
            </span>
            <div class="d-flex gap-2 flex-wrap justify-content-end">
            <a href="{{ route('racks.edit', ['rack' => $rack]) }}" class="btn btn-outline-secondary" title="Renomear ou mudar de Telecommunications Room">
                <i class="fas fa-pen"></i> Editar
            </a>
            <form action="{{ route('racks.destroy', ['rack' => $rack]) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                @php $temItens = $rack->patchPanels->isNotEmpty() || $rack->equipamentos->isNotEmpty(); @endphp
                <button type="submit" class="btn btn-outline-danger"
                        @if($temItens) title="Para excluir, primeiro remova os patch panels e switches deste rack"
                        onclick="alert('O rack {{ $rack->nome }} ainda tem patch panels ou switches. Exclua ou mova-os antes de excluir o rack.'); return false;"
                        @else onclick="return confirm('Excluir o rack {{ $rack->nome }}?')" @endif>
                    <i class="fas fa-trash"></i> Excluir
                </button>
            </form>
            <a href="{{ route('racks.imprimir', ['rack' => $rack]) }}" target="_blank" class="btn btn-outline-dark">
                <i class="fas fa-print"></i> Imprimir / PDF
            </a>
            <a href="{{ $rack->telecomRoom ? route('telecom-rooms.show', ['telecomRoom' => $rack->telecomRoom]) : route('predios.trs', ['predio' => $rack->predio]) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Patch Panels</h2>
                        @can('user')
                        <a href="{{ route('patch-panels.create', ['rack_id' => $rack->id]) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Novo
                        </a>
                        @endcan
                    </div>
                    <div class="card-body">
                        @if($rack->patchPanels->isEmpty())
                        <div class="alert alert-info">Nenhum patch panel cadastrado neste rack.</div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 30px;"></th>
                                        <th>Identificação / Portas</th>
                                        <th width="180px">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-patchpanels" data-rack-id="{{ $rack->id }}">
                                    @foreach($rack->patchPanels as $patchPanel)
                                    <tr data-id="{{ $patchPanel->id }}">
                                        <td class="align-middle" style="cursor: grab;">
                                            <i class="fas fa-grip-vertical text-muted"></i>
                                        </td>
                                        <td>
                                            <strong>{{ $patchPanel->nome }}</strong><br>
                                            <small class="text-muted">{{ $patchPanel->qtde_portas }} portas</small>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('patch-panels.show', ['patchPanel' => $patchPanel]) }}" class="btn btn-info btn-sm">
                                                    Ver
                                                </a>
                                                <a href="{{ route('patch-panels.edit', ['patchPanel' => $patchPanel]) }}" class="btn btn-warning btn-sm">
                                                    Editar
                                                </a>
                                                <a href="{{ route('patch-panels.excluir', ['patchPanel' => $patchPanel]) }}" class="btn btn-danger btn-sm">
                                                    Excluir
                                                </a>
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
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Switches</h2>
                        @can('user')
                        <a href="{{ route('equipamentos.create', ['rack_id' => $rack->id]) }}" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Novo
                        </a>
                        @endcan
                    </div>
                    <div class="card-body">
                        @if($rack->equipamentos->isEmpty())
                        <div class="alert alert-info">Nenhum switch cadastrado neste rack.</div>
                        @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 30px;"></th>
                                        <th>Hostname / Modelo</th>
                                        <th width="180px">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-equipamentos" data-rack-id="{{ $rack->id }}">
                                    @foreach($rack->equipamentos as $equipamento)
                                    <tr data-id="{{ $equipamento->id }}">
                                        <td class="align-middle" style="cursor: grab;">
                                            <i class="fas fa-grip-vertical text-muted"></i>
                                        </td>
                                        <td>
                                            <strong>{{ $equipamento->hostname }}</strong><br>
                                            <small class="text-muted">{{ $equipamento->modeloSwitch?->nome ?? 'Sem modelo' }}</small><br>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                @can('admin')
                                                <a href="{{ route('equipamentos.show', ['equipamento' => $equipamento]) }}" class="btn btn-info btn-sm">
                                                    Ver
                                                </a>
                                                @endcan
                                                <a href="{{ route('equipamentos.edit', ['equipamento' => $equipamento]) }}" class="btn btn-warning btn-sm">
                                                    Editar
                                                </a>
                                                <a href="{{ route('equipamentos.excluir', ['equipamento' => $equipamento]) }}" class="btn btn-danger btn-sm">
                                                    Excluir
                                                </a>
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
            </div>
        </div>

        <!-- Seção dos desenhos -->
        <div class="net-toolbar mt-4" id="net-toolbar">
            <button type="button" class="btn btn-sm btn-outline-primary" id="net-toggle">
                <i class="fas fa-link"></i> Conectar portas
            </button>
            <span class="net-hint" id="net-hint"></span>
            <span class="net-sel-chip" id="net-sel-chip"></span>
        </div>
        <div class="row" id="net-diagramas">
            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-header bg-light">
                        <h3 class="h6 mb-0">Diagrama de Patch Panels</h3>
                    </div>
                    <div class="card-body">
                        @include('racks.partials.patch_panel')
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h3 class="h6 mb-0">Diagrama de Switches</h3>
                    </div>
                    <div class="card-body">
                        @include('racks.partials.equipment')
                    </div>
                </div>
            </div>
        </div>
        @include('racks.partials.conexoes')
    </div>
</div>

@endsection

@section('javascripts_bottom')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
function initTableSortable(tbodyId, tipo) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody || tbody.children.length === 0) return;

    const rackId = tbody.getAttribute('data-rack-id');

    new Sortable(tbody, {
        handle: 'td:first-child',
        animation: 150,
        ghostClass: 'table-active',
        onEnd: function() {
            const itens = [];
            tbody.querySelectorAll('tr[data-id]').forEach((row, index) => {
                itens.push({
                    tipo: tipo,
                    id: row.getAttribute('data-id'),
                    ordem: index + 1
                });
            });

            fetch('/racks/' + rackId + '/reordenar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ itens: itens })
            })
            .then(r => {
                if (!r.ok) throw new Error('Erro ao salvar');
                return r.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            })
            .catch(err => {
                alert('Erro ao reordenar: ' + err.message);
                window.location.reload();
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initTableSortable('tbody-equipamentos', 'equipamento');
    initTableSortable('tbody-patchpanels', 'patchpanel');
});
</script>
@endsection

@extends('main')

@section('content')
<div class="card">
    <div class="card-header bg-usp">
        <h1 class="h4 mb-0 text-dark">
            Vincular Porta de {{ $sala->nome }}
            <small class="text-muted d-block">Rack: {{ $rack->nome }} - Prédio: {{ $sala->predio->nome }}</small>
        </h1>
    </div>
    <div class="card-body">
        @if($patchPanels->isEmpty())
            <div class="alert alert-info">Nenhum patch panel disponível neste rack.</div>
        @else
            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Selecione um Patch Panel</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                @foreach($patchPanels as $pp)
                                    <a href="{{ route('salas.selecionar-patchpanel', ['sala' => $sala, 'rack' => $rack, 'patch_panel_id' => $pp->id]) }}"
                                       class="list-group-item list-group-item-action {{ request('patch_panel_id') == $pp->id ? 'active' : '' }}">
                                        {{ $pp->nome }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                
                @if(request('patch_panel_id'))
                    @php
                        $selectedPP = $patchPanels->firstWhere('id', request('patch_panel_id'));
                        $portasOcupadas = $selectedPP->salasVinculadas->pluck('pivot.porta')->toArray();
                        $tipoPortas = \App\Models\TipoPorta::all();
                    @endphp
                    <div class="col-md-8">
                        <form action="{{ route('salas.vincular-patchpanel', ['sala' => $sala]) }}" method="POST">
                            @csrf
                            <input type="hidden" name="rack_id" value="{{ $rack->id }}">
                            <input type="hidden" name="patch_panel_id" value="{{ $selectedPP->id }}">
                            
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Portas do Patch Panel: {{ $selectedPP->nome }}</h5>
                                    <small class="text-muted">Marque as portas que deseja vincular</small>
                                </div>
                                <div class="card-body">
                                    @if(count($portasOcupadas) >= $selectedPP->qtde_portas)
                                        <div class="alert alert-warning">
                                            Todas as portas deste patch panel estão ocupadas.
                                        </div>
                                    @else
                                        <div class="row">
                                            @foreach(range(1, $selectedPP->qtde_portas) as $i)
                                                @if(!in_array($i, $portasOcupadas))
                                                    <div class="col-md-6 col-sm-6 col-12 mb-3">
                                                        <div class="card">
                                                            <div class="card-body">
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input" type="checkbox" 
                                                                        name="portas[]" 
                                                                        id="porta-{{ $i }}" 
                                                                        value="{{ $i }}">
                                                                    <label class="form-check-label" for="porta-{{ $i }}">
                                                                        <strong>Porta {{ $i }}</strong>
                                                                    </label>
                                                                </div>
                                                                
                                                                <div class="form-group">
                                                                    <label for="tipo_porta_{{ $i }}" class="small">Tipo de Porta (Opcional):</label>
                                                                    <select class="form-control form-control-sm" 
                                                                        name="tipos_porta[{{ $i }}]" 
                                                                        id="tipo_porta_{{ $i }}">
                                                                        <option value="">-- Não informar --</option>
                                                                        @foreach($tipoPortas as $tipo)
                                                                            <option value="{{ $tipo->id }}">{{ $tipo->nome }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="card-footer bg-light">
                                    <div class="d-flex justify-content-between">
                                        <a href="{{ route('salas.selecionar-rack', ['sala' => $sala]) }}" class="btn btn-secondary">Voltar</a>
                                        @if(count($portasOcupadas) < $selectedPP->qtde_portas)
                                            <button type="submit" class="btn btn-primary">Vincular Portas Selecionadas</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="col-md-8">
                        <div class="alert alert-info">
                            Selecione um Patch Panel à esquerda para visualizar suas portas.
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

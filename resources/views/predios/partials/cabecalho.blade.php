{{-- Cabeçalho do prédio com navegação entre as seções. Espera $predio e $aba --}}
@php
    $abas = [
        'show' => ['Visão geral', 'fa-building', route('predios.show', ['predio' => $predio])],
        'trs' => ['Telecommunications Rooms', 'fa-door-closed', route('predios.trs', ['predio' => $predio])],
        'salas' => ['Salas/Ambientes', 'fa-door-open', route('predios.salas', ['predio' => $predio])],
        'plantas' => ['Plantas', 'fa-map', route('plantas.index', ['predio' => $predio])],
    ];
@endphp
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
    <div>
        <h1 class="h4 mb-0"><i class="fas fa-building"></i> {{ $predio->nome }}</h1>
        @if($predio->descricao)
            <div class="text-muted">{{ $predio->descricao }}</div>
        @endif
    </div>
    <a href="{{ route('predios.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Prédios</a>
</div>
<ul class="nav nav-tabs mb-3">
    @foreach($abas as $chave => [$rotulo, $icone, $url])
        <li class="nav-item">
            <a class="nav-link {{ $aba === $chave ? 'active' : '' }}" href="{{ $url }}"><i class="fas {{ $icone }} me-1"></i>{{ $rotulo }}</a>
        </li>
    @endforeach
</ul>

@extends('main')

@section('content')
<style>
    .secao-tile { display: block; text-decoration: none; color: inherit; border-radius: 12px; border-top: 4px solid #1094ab; transition: box-shadow .15s, transform .15s; }
    .secao-tile:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1.25rem rgba(0,0,0,.12) !important; color: inherit; }
    .secao-tile .icone { font-size: 1.8rem; color: #1094ab; }
    .secao-tile .numero { font-size: 2rem; font-weight: 700; line-height: 1; }
    .secao-tile .rotulo { text-transform: uppercase; font-size: .8rem; letter-spacing: .04em; color: #6c757d; }
</style>

@include('predios.partials.cabecalho', ['aba' => 'show'])

<div class="row g-3">
    <div class="col-md-4">
        <a href="{{ route('predios.trs', ['predio' => $predio]) }}" class="card shadow-sm secao-tile h-100">
            <div class="card-body text-center">
                <div class="icone"><i class="fas fa-door-closed"></i></div>
                <div class="numero mt-2">{{ $predio->telecom_rooms_count }}</div>
                <div class="rotulo">Telecommunications Rooms</div>
                <small class="text-muted d-block mt-2">Cadastrar e editar Telecommunications Rooms e os racks de cada uma</small>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('predios.salas', ['predio' => $predio]) }}" class="card shadow-sm secao-tile h-100">
            <div class="card-body text-center">
                <div class="icone"><i class="fas fa-door-open"></i></div>
                <div class="numero mt-2">{{ $predio->salas_count }}</div>
                <div class="rotulo">Salas/Ambientes</div>
                <small class="text-muted d-block mt-2">Salas, corredores, saguões e outros locais</small>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('plantas.index', ['predio' => $predio]) }}" class="card shadow-sm secao-tile h-100">
            <div class="card-body text-center">
                <div class="icone"><i class="fas fa-map"></i></div>
                <div class="numero mt-2">{{ $predio->plantas_count }}</div>
                <div class="rotulo">Plantas baixas</div>
                <small class="text-muted d-block mt-2">Enviar plantas e marcar pontos</small>
            </div>
        </a>
    </div>
</div>
@endsection

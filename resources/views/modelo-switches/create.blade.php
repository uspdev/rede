@extends('main')

@section('content')
<div class="card">
  <div class="card-header bg-usp">
      <span class="h4 mb-0 text-dark"><i class="fas fa-plus"></i> Cadastrar Modelo de Switch</span>
  </div>
  <div class="card-body">
    <form action="/modelo-switches" method="POST">
      @csrf
      <div class="col-md-3">
        <div class="mb-3">
          <label class="form-label">Fabricante *</label>
          <input type="text" name="fabricante" class="form-control" value="{{ old('fabricante') }}" placeholder="Ex: HP, Cisco, Aruba">
        </div>
        <div class="mb-3">
          <label class="form-label">Nome/Modelo *</label>
          <input type="text" name="nome" class="form-control" value="{{ old('nome') }}" placeholder="Ex: 1920S 24G">
        </div>
        <div class="mb-3">
          <label class="form-label">Quantidade de Portas *</label>
          <input type="number" name="qtde_portas" class="form-control" value="{{ old('qtde_portas') }}" min="1">
        </div>
        <div class="mb-3 form-check">
          <input class="form-check-input" type="checkbox" value="1" name="poe" id="poe" @checked(old('poe'))>
          <label class="form-check-label" for="poe">
            PoE
          </label>
        </div>
        <div class="d-flex justify-content-between">
          <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar</button>
          <a href="/modelo-switches" class="btn btn-secondary">Cancelar</a>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

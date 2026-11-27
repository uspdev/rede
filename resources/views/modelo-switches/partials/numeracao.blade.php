@php $numeracaoAtual = old('numeracao_portas', $valor ?? 'horizontal'); @endphp
<style>
  .num-opcao { cursor: pointer; border: 2px solid #dee2e6; border-radius: 6px; padding: 8px 10px; display: block; }
  .num-opcao:hover { border-color: #adb5bd; }
  .btn-check:checked + .num-opcao { border-color: #0d6efd; background: rgba(13,110,253,.06); }
  .num-grid { display: grid; grid-template-columns: repeat(6, 22px); gap: 3px; margin-top: 6px; }
  .num-grid span { font: 600 10px/20px system-ui, sans-serif; text-align: center; background: #343a40; color: #fff; border-radius: 3px; }
</style>
<div class="mb-3">
  <label class="form-label">Numeração das portas *</label>
  <div class="d-flex flex-column gap-2">
    <div>
      <input type="radio" class="btn-check" name="numeracao_portas" id="num_horizontal" value="horizontal" @checked($numeracaoAtual === 'horizontal')>
      <label class="num-opcao" for="num_horizontal">
        <strong>Horizontal</strong>
        <small class="text-muted d-block">Linha de cima em sequência; a de baixo continua.</small>
        <div class="num-grid">
          @foreach([1,2,3,4,5,6,7,8,9,10,11,12] as $n)<span>{{ $n }}</span>@endforeach
        </div>
      </label>
    </div>
    <div>
      <input type="radio" class="btn-check" name="numeracao_portas" id="num_vertical" value="vertical" @checked($numeracaoAtual === 'vertical')>
      <label class="num-opcao" for="num_vertical">
        <strong>Vertical</strong>
        <small class="text-muted d-block">Ímpares em cima, pares embaixo.</small>
        <div class="num-grid">
          @foreach([1,3,5,7,9,11,2,4,6,8,10,12] as $n)<span>{{ $n }}</span>@endforeach
        </div>
      </label>
    </div>
  </div>
</div>

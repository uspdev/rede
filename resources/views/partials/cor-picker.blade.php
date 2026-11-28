{{--
    Seletor de cor com paleta pronta + cor personalizada + pré-visualização.
    Parâmetros: $name (nome do campo), $valor (cor atual), $ajuda (texto opcional), $previewTexto (opcional)
--}}
@php
    $paleta = [
        '#DC3545' => 'Vermelho', '#FD7E14' => 'Laranja', '#FFC107' => 'Amarelo', '#8BC34A' => 'Verde-limão',
        '#198754' => 'Verde', '#20C997' => 'Verde-água', '#0DCAF0' => 'Ciano', '#0D6EFD' => 'Azul',
        '#3F51B5' => 'Índigo', '#6F42C1' => 'Roxo', '#D63384' => 'Rosa', '#795548' => 'Marrom',
        '#6C757D' => 'Cinza', '#212529' => 'Preto',
    ];
    $valor = strtoupper($valor ?: array_key_first($paleta));
    $uid = 'cor-' . uniqid();
@endphp
<style>
    .cor-picker .cor-swatches { display: flex; flex-wrap: wrap; gap: 8px; }
    .cor-picker .cor-sw {
        width: 32px; height: 32px; border-radius: 50%; border: 2px solid #fff; cursor: pointer; padding: 0;
        box-shadow: 0 0 0 1px rgba(0,0,0,.2); transition: transform .1s, box-shadow .1s; position: relative;
    }
    .cor-picker .cor-sw:hover { transform: scale(1.12); }
    .cor-picker .cor-sw.sel { box-shadow: 0 0 0 2px #fff, 0 0 0 4px #0d6efd; }
    .cor-picker .cor-sw.sel::after {
        content: "✓"; position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 700; font-size: 14px; text-shadow: 0 0 2px rgba(0,0,0,.6);
    }
    .cor-picker .cor-custom { position: relative; overflow: hidden; background: conic-gradient(red, yellow, lime, cyan, blue, magenta, red); }
    .cor-picker .cor-custom input[type=color] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; border: 0; padding: 0; }
    .cor-picker .cor-linha { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 10px; }
    .cor-picker .cor-hex { width: 120px; font-family: monospace; text-transform: uppercase; }
    .cor-picker .cor-preview { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #495057; }
    .cor-picker .cor-preview .chip { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #cbd5e1; border-radius: 999px; padding: 3px 10px 3px 6px; background: #fff; }
    .cor-picker .cor-preview .chip i { width: 12px; height: 12px; border-radius: 3px; border: 1px solid rgba(0,0,0,.25); display: inline-block; }
    .cor-picker .cor-preview .jack { width: 22px; height: 18px; border-radius: 3px; display: inline-block; box-shadow: inset 0 0 0 1px rgba(0,0,0,.35); }
</style>
<div class="cor-picker" id="{{ $uid }}">
    <div class="cor-swatches" role="radiogroup" aria-label="Cor">
        @foreach($paleta as $hex => $nomeCor)
            <button type="button" class="cor-sw" data-cor="{{ $hex }}" title="{{ $nomeCor }}" aria-label="{{ $nomeCor }}" style="background: {{ $hex }};"></button>
        @endforeach
        <label class="cor-sw cor-custom mb-0" title="Cor personalizada" aria-label="Cor personalizada">
            <input type="color" value="{{ $valor }}">
        </label>
    </div>
    <div class="cor-linha">
        <div class="input-group input-group-sm" style="width: auto;">
            <span class="input-group-text">Hex</span>
            <input type="text" class="form-control cor-hex" name="{{ $name }}" value="{{ $valor }}" maxlength="7" pattern="#[0-9A-Fa-f]{6}" placeholder="#RRGGBB">
        </div>
        <span class="cor-preview">
            Prévia:
            <span class="chip"><i></i><span class="cor-preview-texto">{{ $previewTexto ?? 'Exemplo' }}</span></span>
            <span class="jack" title="Como aparece na porta"></span>
        </span>
    </div>
    @isset($ajuda)
        <div class="form-text">{{ $ajuda }}</div>
    @endisset
</div>
<script>
(function () {
    const root = document.getElementById(@json($uid));
    const hex = root.querySelector('.cor-hex');
    const nativo = root.querySelector('input[type=color]');
    const custom = root.querySelector('.cor-custom');
    const valida = v => /^#[0-9A-F]{6}$/i.test(v);

    function aplicar(cor, origem) {
        cor = (cor || '').toUpperCase();
        if (!cor.startsWith('#')) cor = '#' + cor;
        if (origem !== 'hex') hex.value = cor;
        hex.classList.toggle('is-invalid', !valida(cor));
        if (!valida(cor)) return;
        if (origem !== 'nativo') nativo.value = cor;
        let naPaleta = false;
        root.querySelectorAll('.cor-sw[data-cor]').forEach(b => {
            const sel = b.dataset.cor === cor;
            b.classList.toggle('sel', sel);
            naPaleta = naPaleta || sel;
        });
        custom.classList.toggle('sel', !naPaleta);
        custom.style.background = naPaleta ? '' : cor;
        root.querySelector('.cor-preview .chip i').style.background = cor;
        root.querySelector('.cor-preview .jack').style.background = cor;
    }

    root.querySelectorAll('.cor-sw[data-cor]').forEach(b => b.addEventListener('click', () => aplicar(b.dataset.cor)));
    nativo.addEventListener('input', () => aplicar(nativo.value, 'nativo'));
    hex.addEventListener('input', () => aplicar(hex.value, 'hex'));

    // Prévia acompanha o nome digitado no formulário
    const nome = root.closest('form')?.querySelector('[name=nome]');
    const txt = root.querySelector('.cor-preview-texto');
    if (nome) {
        const sync = () => txt.textContent = nome.value.trim() || 'Exemplo';
        nome.addEventListener('input', sync);
        sync();
    }

    aplicar(hex.value);
})();
</script>

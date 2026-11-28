{{--
    Botão "Tela cheia" para as telas de marcação da planta.
    Na página: marque o bloco de trabalho com data-foco-alvo e os elementos que devem
    crescer até o fim da tela com data-foco-altura (ex.: a planta e a lista lateral).
    Inclua este partial onde o botão deve aparecer.
--}}
<button type="button" class="tela-cheia-btn" data-tela-cheia title="Usar a tela inteira (esconde o menu do site)">
    <span class="entrar">⛶ Tela cheia</span><span class="sair">✕ Sair da tela cheia</span>
</button>

@once
<style>
    .tela-cheia-btn { border: 1px solid #cbd5e1; background: #fff; color: #1e293b; border-radius: 4px; padding: 4px 10px; font-size: 13px; cursor: pointer; white-space: nowrap; }
    .tela-cheia-btn:hover { background: #f1f5f9; }
    .tela-cheia-btn .sair { display: none; }
    body.modo-foco .tela-cheia-btn { background: #1e293b; color: #fff; border-color: #1e293b; }
    body.modo-foco .tela-cheia-btn .entrar { display: none; }
    body.modo-foco .tela-cheia-btn .sair { display: inline; }

    body.modo-foco { overflow: hidden; }
    body.modo-foco [data-foco-alvo] {
        position: fixed; inset: 0; z-index: 1040; margin: 0 !important; max-width: none !important;
        background: #fff; padding: 8px 10px; overflow: auto;
    }
</style>
<script>
(function () {
    let alturasOriginais = new Map();

    function ajustarAlturas() {
        document.querySelectorAll('[data-foco-alvo] [data-foco-altura]').forEach(el => {
            if (!alturasOriginais.has(el)) alturasOriginais.set(el, el.style.height);
            const topo = el.getBoundingClientRect().top;
            el.style.height = Math.max(200, window.innerHeight - topo - 10) + 'px';
        });
    }
    function avisarRedimensionou() {
        // panzoom, cabos e afins recalculam com o novo tamanho
        window.dispatchEvent(new Event('resize'));
    }

    function entrar() {
        document.body.classList.add('modo-foco');
        const raiz = document.documentElement;
        if (raiz.requestFullscreen && !document.fullscreenElement) {
            raiz.requestFullscreen().catch(() => {});
        }
        requestAnimationFrame(() => { ajustarAlturas(); avisarRedimensionou(); });
        try { sessionStorage.setItem('modo-foco:' + location.pathname, '1'); } catch (e) {}
    }
    function sair() {
        document.body.classList.remove('modo-foco');
        alturasOriginais.forEach((h, el) => el.style.height = h);
        alturasOriginais = new Map();
        if (document.fullscreenElement && document.exitFullscreen) document.exitFullscreen().catch(() => {});
        requestAnimationFrame(avisarRedimensionou);
        try { sessionStorage.removeItem('modo-foco:' + location.pathname); } catch (e) {}
    }

    document.addEventListener('click', e => {
        if (!e.target.closest('[data-tela-cheia]')) return;
        document.body.classList.contains('modo-foco') ? sair() : entrar();
    });
    // a janela mudou de tamanho (inclusive ao entrar/sair da tela cheia do navegador)
    let timer;
    window.addEventListener('resize', e => {
        if (!document.body.classList.contains('modo-foco') || !e.isTrusted) return;
        clearTimeout(timer);
        timer = setTimeout(() => { ajustarAlturas(); avisarRedimensionou(); }, 80);
    });

    // Recarregou a página (ex.: depois de salvar) estando no modo foco: continua nele
    // (sem a tela cheia do navegador, que exige um clique do usuário)
    try {
        if (sessionStorage.getItem('modo-foco:' + location.pathname) === '1') {
            document.addEventListener('DOMContentLoaded', () => {
                document.body.classList.add('modo-foco');
                requestAnimationFrame(() => { ajustarAlturas(); avisarRedimensionou(); });
            });
        }
    } catch (e) {}
})();
</script>
@endonce

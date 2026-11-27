<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Teste mínimo para manter o Dusk (e o workflow de CI) funcionando
 * enquanto os testes de navegador são refeitos.
 */
class OlaMundoTest extends DuskTestCase
{
    public function test_ola_mundo(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->assertSourceHas('</html>');
        });
    }
}

<?php

declare(strict_types=1);

namespace Tests\Veiculos\Application\Gateway;

use App\Veiculos\Application\Gateway\FiltroListagemVeiculo;
use PHPUnit\Framework\TestCase;

final class FiltroListagemVeiculoTest extends TestCase {
    public function testOffsetNaPrimeiraPaginaEZero(): void {
        $filtro = new FiltroListagemVeiculo(placa: null, marca: null, modelo: null, pagina: 1, porPagina: 20);

        $this->assertSame(0, $filtro->offset());
    }

    public function testOffsetEmPaginasSeguintes(): void {
        $filtro = new FiltroListagemVeiculo(placa: null, marca: null, modelo: null, pagina: 3, porPagina: 20);

        $this->assertSame(40, $filtro->offset());
    }
}

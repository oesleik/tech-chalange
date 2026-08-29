<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Domain\Entity;

use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use PHPUnit\Framework\TestCase;

final class PecaOrdemServicoTest extends TestCase {
    public function testGettersRetornamValoresInformados(): void {
        $peca = new PecaOrdemServico(idPeca: 1, quantidade: 3, valorUnitario: 10.5);

        $this->assertSame(1, $peca->idPeca());
        $this->assertSame(3, $peca->quantidade());
        $this->assertSame(10.5, $peca->valorUnitario());
    }

    public function testSubtotalMultiplicaQuantidadePeloValorUnitario(): void {
        $peca = new PecaOrdemServico(idPeca: 1, quantidade: 3, valorUnitario: 10.5);

        $this->assertSame(31.5, $peca->subtotal());
    }

    public function testSubtotalArredondaParaDuasCasasDecimais(): void {
        $peca = new PecaOrdemServico(idPeca: 1, quantidade: 3, valorUnitario: 0.1);

        $this->assertSame(0.3, $peca->subtotal());
    }
}

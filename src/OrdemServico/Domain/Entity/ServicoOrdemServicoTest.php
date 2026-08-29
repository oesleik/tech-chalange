<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Domain\Entity;

use App\OrdemServico\Domain\Entity\ServicoOrdemServico;
use PHPUnit\Framework\TestCase;

final class ServicoOrdemServicoTest extends TestCase {
    public function testGettersRetornamValoresInformados(): void {
        $servico = new ServicoOrdemServico(idServico: 2, quantidade: 1, valorUnitario: 150);

        $this->assertSame(2, $servico->idServico());
        $this->assertSame(1, $servico->quantidade());
        $this->assertSame(150.0, $servico->valorUnitario());
    }

    public function testSubtotalMultiplicaQuantidadePeloValorUnitario(): void {
        $servico = new ServicoOrdemServico(idServico: 2, quantidade: 2, valorUnitario: 150);

        $this->assertSame(300.0, $servico->subtotal());
    }
}

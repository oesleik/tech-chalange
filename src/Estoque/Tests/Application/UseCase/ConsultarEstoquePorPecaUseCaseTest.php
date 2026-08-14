<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Application\UseCase;

use App\Estoque\Application\Gateway\EstoqueGatewayInterface;
use App\Estoque\Application\UseCase\ConsultarEstoquePorPeca\ConsultarEstoquePorPecaUseCase;
use App\Estoque\Domain\Exception\PecaNaoEncontradaException;
use PHPUnit\Framework\TestCase;

final class ConsultarEstoquePorPecaUseCaseTest extends TestCase {
    public function testRetornaEstoqueAtualComSucesso(): void {
        $gateway = $this->createMock(EstoqueGatewayInterface::class);
        $gateway->method('pecaExiste')->with(1)->willReturn(true);
        $gateway->method('calcularEstoqueAtual')->with(1)->willReturn(10);

        $resultado = new ConsultarEstoquePorPecaUseCase($gateway)->executar(1);

        $this->assertSame(1, $resultado->pecaId);
        $this->assertSame(10, $resultado->estoqueAtual);
    }

    public function testLancaExcecaoQuandoPecaNaoExiste(): void {
        $gateway = $this->createMock(EstoqueGatewayInterface::class);
        $gateway->method('pecaExiste')->willReturn(false);

        $this->expectException(PecaNaoEncontradaException::class);
        new ConsultarEstoquePorPecaUseCase($gateway)->executar(99);
    }
}

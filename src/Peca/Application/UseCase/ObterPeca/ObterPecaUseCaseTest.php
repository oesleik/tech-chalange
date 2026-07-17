<?php

declare(strict_types=1);

namespace Tests\Peca\Application\UseCase;

use App\Peca\Application\Gateway\PecaGatewayInterface;
use App\Peca\Application\UseCase\ObterPeca\ObterPecaUseCase;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\Exception\PecaNaoEncontradaException;
use App\Peca\Domain\ValueObject\ValorUnitario;
use PHPUnit\Framework\TestCase;

final class ObterPecaUseCaseTest extends TestCase {
    public function testRetornaPecaQuandoEncontrada(): void {
        $peca = Peca::reconstituir(1, 'Filtro de óleo', new ValorUnitario(49.90));

        $gateway = $this->createMock(PecaGatewayInterface::class);
        $gateway->method('buscarPorId')->with(1)->willReturn($peca);

        $resultado = (new ObterPecaUseCase($gateway))->executar(1);

        $this->assertSame(1, $resultado->id());
        $this->assertSame('Filtro de óleo', $resultado->descricao());
    }

    public function testLancaExcecaoQuandoNaoEncontrada(): void {
        $gateway = $this->createMock(PecaGatewayInterface::class);
        $gateway->method('buscarPorId')->with(99)->willReturn(null);

        $this->expectException(PecaNaoEncontradaException::class);
        (new ObterPecaUseCase($gateway))->executar(99);
    }
}
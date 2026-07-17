<?php

declare(strict_types=1);

namespace Tests\Peca\Application\UseCase;

use App\Peca\Application\Gateway\PecaGatewayInterface;
use App\Peca\Application\UseCase\CriarPeca\CriarPecaInputDTO;
use App\Peca\Application\UseCase\CriarPeca\CriarPecaUseCase;
use App\Peca\Domain\Entity\Peca;
use PHPUnit\Framework\TestCase;

final class CriarPecaUseCaseTest extends TestCase {
    public function testCriaPecaComSucesso(): void {
        $gateway = $this->createMock(PecaGatewayInterface::class);
        $gateway->method('inserir')->willReturnCallback(
            fn(Peca $p) => $p->comId(1),
        );

        $resultado = (new CriarPecaUseCase($gateway))->executar(
            new CriarPecaInputDTO('Filtro de óleo', 49.90),
        );

        $this->assertSame(1, $resultado->id());
        $this->assertSame('Filtro de óleo', $resultado->descricao());
        $this->assertSame(49.90, $resultado->valorUnitario()->getValue());
    }

    public function testLancaExcecaoQuandoValorNegativo(): void {
        $gateway = $this->createMock(PecaGatewayInterface::class);
        $gateway->expects($this->never())->method('inserir');

        $this->expectException(\InvalidArgumentException::class);
        (new CriarPecaUseCase($gateway))->executar(
            new CriarPecaInputDTO('Filtro de óleo', -10.0),
        );
    }
}
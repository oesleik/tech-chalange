<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Application\UseCase;

use App\Estoque\Application\Gateway\EstoqueGatewayInterface;
use App\Estoque\Application\UseCase\RegistrarEntradaEstoque\RegistrarEntradaEstoqueInputDTO;
use App\Estoque\Application\UseCase\RegistrarEntradaEstoque\RegistrarEntradaEstoqueUseCase;
use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;
use App\Estoque\Domain\Exception\PecaNaoEncontradaException;
use PHPUnit\Framework\TestCase;

final class RegistrarEntradaEstoqueUseCaseTest extends TestCase {
    public function testRegistraEntradaComSucesso(): void {
        $gateway = $this->createMock(EstoqueGatewayInterface::class);
        $gateway->method('pecaExiste')->with(10)->willReturn(true);
        $gateway->method('inserirLancamento')->willReturn(
            LancamentoEstoque::reconstituir(1, 10, 5, TipoLancamentoEnum::ENTRADA)
        );

        $resultado = new RegistrarEntradaEstoqueUseCase($gateway)
            ->executar(new RegistrarEntradaEstoqueInputDTO(10, 5));

        $this->assertSame(TipoLancamentoEnum::ENTRADA, $resultado->tipo());
        $this->assertSame(5, $resultado->quantidade());
        $this->assertSame(10, $resultado->pecaId());
    }

    public function testLancaExcecaoQuandoPecaNaoExiste(): void {
        $gateway = $this->createMock(EstoqueGatewayInterface::class);
        $gateway->method('pecaExiste')->willReturn(false);
        $gateway->expects($this->never())->method('inserirLancamento');

        $this->expectException(PecaNaoEncontradaException::class);
        new RegistrarEntradaEstoqueUseCase($gateway)
            ->executar(new RegistrarEntradaEstoqueInputDTO(99, 5));
    }
}

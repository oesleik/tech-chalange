<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Application\UseCase;

use App\Estoque\Application\Gateway\EstoqueGatewayInterface;
use App\Estoque\Application\UseCase\RegistrarBaixaEstoque\RegistrarBaixaEstoqueInputDTO;
use App\Estoque\Application\UseCase\RegistrarBaixaEstoque\RegistrarBaixaEstoqueUseCase;
use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;
use App\Estoque\Domain\Exception\EstoqueInsuficienteException;
use App\Estoque\Domain\Exception\PecaNaoEncontradaException;
use PHPUnit\Framework\TestCase;

final class RegistrarBaixaEstoqueUseCaseTest extends TestCase
{
    public function testRegistraBaixaComSucesso(): void
    {
        $gateway = $this->createMock(EstoqueGatewayInterface::class);
        $gateway->method('pecaExiste')->with(10)->willReturn(true);
        $gateway->method('calcularEstoqueAtual')->with(10)->willReturn(10);
        $gateway->method('inserirLancamento')->willReturn(
            LancamentoEstoque::reconstituir(2, 10, 4, TipoLancamentoEnum::BAIXA)
        );

        $resultado = (new RegistrarBaixaEstoqueUseCase($gateway))
            ->executar(new RegistrarBaixaEstoqueInputDTO(10, 4));

        $this->assertSame(TipoLancamentoEnum::BAIXA, $resultado->tipo());
        $this->assertSame(4, $resultado->quantidade());
    }

    public function testLancaExcecaoQuandoEstoqueInsuficiente(): void
    {
        $gateway = $this->createMock(EstoqueGatewayInterface::class);
        $gateway->method('pecaExiste')->willReturn(true);
        $gateway->method('calcularEstoqueAtual')->willReturn(3);
        $gateway->expects($this->never())->method('inserirLancamento');

        $this->expectException(EstoqueInsuficienteException::class);
        (new RegistrarBaixaEstoqueUseCase($gateway))
            ->executar(new RegistrarBaixaEstoqueInputDTO(10, 10));
    }

    public function testLancaExcecaoQuandoPecaNaoExiste(): void
    {
        $gateway = $this->createMock(EstoqueGatewayInterface::class);
        $gateway->method('pecaExiste')->willReturn(false);

        $this->expectException(PecaNaoEncontradaException::class);
        (new RegistrarBaixaEstoqueUseCase($gateway))
            ->executar(new RegistrarBaixaEstoqueInputDTO(99, 5));
    }
}
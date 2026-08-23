<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Application\UseCase;

use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoUseCase;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use App\OrdemServico\Domain\Entity\ServicoOrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use DateTime;
use PHPUnit\Framework\TestCase;

final class ObterOrdemServicoUseCaseTest extends TestCase {
    public function testObtemOrdemServicoComPecasEServicos(): void {
        $os = new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::RECEBIDA, 0, new DateTime());
        $pecas = [new PecaOrdemServico(1, 2, 10.0)];
        $servicos = [new ServicoOrdemServico(2, 1, 20.0)];

        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->expects($this->once())->method('buscarPorId')->with(1)->willReturn($os);

        $itensGateway = $this->createMock(ItensOrdemServicoGatewayInterface::class);
        $itensGateway->expects($this->once())->method('buscarPecasPorOrdemServico')->with(1)->willReturn($pecas);
        $itensGateway->expects($this->once())->method('buscarServicosPorOrdemServico')->with(1)->willReturn($servicos);

        $resultado = new ObterOrdemServicoUseCase($gateway, $itensGateway)->executar(1);

        $this->assertSame($os, $resultado->ordemServico);
        $this->assertSame($pecas, $resultado->pecas);
        $this->assertSame($servicos, $resultado->servicos);
    }

    public function testLancaExcecaoQuandoNaoEncontrada(): void {
        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->willReturn(null);

        $itensGateway = $this->createMock(ItensOrdemServicoGatewayInterface::class);

        $this->expectException(OrdemServicoNaoEncontradaException::class);
        new ObterOrdemServicoUseCase($gateway, $itensGateway)->executar(99);
    }
}

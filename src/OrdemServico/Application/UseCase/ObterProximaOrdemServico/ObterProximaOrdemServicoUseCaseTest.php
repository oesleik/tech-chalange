<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Application\UseCase;

use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Application\UseCase\ObterProximaOrdemServico\ObterProximaOrdemServicoUseCase;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use DateTime;
use PHPUnit\Framework\TestCase;

final class ObterProximaOrdemServicoUseCaseTest extends TestCase {
    public function testRetornaNullQuandoNaoHaProximaNaFila(): void {
        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->method('obterProximaNaFila')->willReturn(null);

        $itensGateway = $this->createMock(ItensOrdemServicoGatewayInterface::class);
        $itensGateway->expects($this->never())->method('buscarPecasPorOrdemServico');

        $resultado = new ObterProximaOrdemServicoUseCase($gateway, $itensGateway)->executar();

        $this->assertNull($resultado);
    }

    public function testRetornaOutputDTOComItensQuandoExisteProximaNaFila(): void {
        $os = new OrdemServico(5, 10, 20, SituacaoOrdemServicoEnum::APROVADA, 0, new DateTime());

        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->method('obterProximaNaFila')->willReturn($os);

        $itensGateway = $this->createMock(ItensOrdemServicoGatewayInterface::class);
        $itensGateway->expects($this->once())->method('buscarPecasPorOrdemServico')->with(5)->willReturn([]);
        $itensGateway->expects($this->once())->method('buscarServicosPorOrdemServico')->with(5)->willReturn([]);

        $resultado = new ObterProximaOrdemServicoUseCase($gateway, $itensGateway)->executar();

        $this->assertSame($os, $resultado->ordemServico);
    }
}

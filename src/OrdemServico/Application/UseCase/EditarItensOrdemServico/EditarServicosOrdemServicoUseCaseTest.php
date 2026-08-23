<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Application\UseCase;

use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Application\UseCase\EditarItensOrdemServico\EditarItensInputDTO;
use App\OrdemServico\Application\UseCase\EditarItensOrdemServico\EditarServicosOrdemServicoUseCase;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Entity\ServicoOrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use DateTime;
use PHPUnit\Framework\TestCase;

final class EditarServicosOrdemServicoUseCaseTest extends TestCase {
    public function testAdicionaServicosQuandoNaoDeveSubstituir(): void {
        $os = $this->ordemServico();

        $ordemServicoGateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $ordemServicoGateway->expects($this->once())->method('buscarPorId')->with(1)->willReturn($os);

        $itensGateway = $this->createMock(ItensOrdemServicoGatewayInterface::class);
        $itensGateway->expects($this->once())
            ->method('adicionarServicos')
            ->with($os, $this->callback(fn(array $servicos) => $servicos[0] instanceof ServicoOrdemServico && $servicos[0]->idServico() === 7));
        $itensGateway->expects($this->never())->method('substituirServicos');

        new EditarServicosOrdemServicoUseCase($ordemServicoGateway, $itensGateway)->executar(
            new EditarItensInputDTO(1, [['id' => 7, 'quantidade' => 3]], false),
        );
    }

    public function testSubstituiServicosQuandoFlagAtiva(): void {
        $os = $this->ordemServico();

        $ordemServicoGateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $ordemServicoGateway->method('buscarPorId')->willReturn($os);

        $itensGateway = $this->createMock(ItensOrdemServicoGatewayInterface::class);
        $itensGateway->expects($this->once())->method('substituirServicos');
        $itensGateway->expects($this->never())->method('adicionarServicos');

        new EditarServicosOrdemServicoUseCase($ordemServicoGateway, $itensGateway)->executar(
            new EditarItensInputDTO(1, [['id' => 7, 'quantidade' => 3]], true),
        );
    }

    public function testLancaExcecaoQuandoOrdemNaoEncontrada(): void {
        $ordemServicoGateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $ordemServicoGateway->method('buscarPorId')->willReturn(null);

        $itensGateway = $this->createMock(ItensOrdemServicoGatewayInterface::class);

        $this->expectException(OrdemServicoNaoEncontradaException::class);
        new EditarServicosOrdemServicoUseCase($ordemServicoGateway, $itensGateway)->executar(
            new EditarItensInputDTO(99, [], false),
        );
    }

    private function ordemServico(): OrdemServico {
        return new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::RECEBIDA, 0, new DateTime());
    }
}

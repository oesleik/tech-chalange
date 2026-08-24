<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Application\UseCase;

use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Application\UseCase\EditarItensOrdemServico\EditarItensInputDTO;
use App\OrdemServico\Application\UseCase\EditarItensOrdemServico\EditarPecasOrdemServicoUseCase;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use DateTime;
use PHPUnit\Framework\TestCase;

final class EditarPecasOrdemServicoUseCaseTest extends TestCase {
    public function testAdicionaPecasQuandoNaoDeveSubstituir(): void {
        $os = $this->ordemServico();

        $ordemServicoGateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $ordemServicoGateway->expects($this->once())->method('buscarPorId')->with(1)->willReturn($os);

        $itensGateway = $this->createMock(ItensOrdemServicoGatewayInterface::class);
        $itensGateway->expects($this->once())
            ->method('adicionarPecas')
            ->with($os, $this->callback(fn(array $pecas) => $pecas[0] instanceof PecaOrdemServico && $pecas[0]->idPeca() === 5));
        $itensGateway->expects($this->never())->method('substituirPecas');

        new EditarPecasOrdemServicoUseCase($ordemServicoGateway, $itensGateway)->executar(
            new EditarItensInputDTO(1, [['id' => 5, 'quantidade' => 2]], false),
        );
    }

    public function testSubstituiPecasQuandoFlagAtiva(): void {
        $os = $this->ordemServico();

        $ordemServicoGateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $ordemServicoGateway->method('buscarPorId')->willReturn($os);

        $itensGateway = $this->createMock(ItensOrdemServicoGatewayInterface::class);
        $itensGateway->expects($this->once())->method('substituirPecas');
        $itensGateway->expects($this->never())->method('adicionarPecas');

        new EditarPecasOrdemServicoUseCase($ordemServicoGateway, $itensGateway)->executar(
            new EditarItensInputDTO(1, [['id' => 5, 'quantidade' => 2]], true),
        );
    }

    public function testLancaExcecaoQuandoOrdemNaoEncontrada(): void {
        $ordemServicoGateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $ordemServicoGateway->method('buscarPorId')->willReturn(null);

        $itensGateway = $this->createMock(ItensOrdemServicoGatewayInterface::class);

        $this->expectException(OrdemServicoNaoEncontradaException::class);
        new EditarPecasOrdemServicoUseCase($ordemServicoGateway, $itensGateway)->executar(
            new EditarItensInputDTO(99, [], false),
        );
    }

    private function ordemServico(): OrdemServico {
        return new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::RECEBIDA, 0, new DateTime());
    }
}

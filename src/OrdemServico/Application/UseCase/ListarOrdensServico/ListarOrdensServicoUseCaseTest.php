<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Application\UseCase;

use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Application\UseCase\ListarOrdensServico\ListarOrdensServicoInputDTO;
use App\OrdemServico\Application\UseCase\ListarOrdensServico\ListarOrdensServicoUseCase;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Domain\ValueObject\FiltroOrdemServico;
use DateTime;
use PHPUnit\Framework\TestCase;

final class ListarOrdensServicoUseCaseTest extends TestCase {
    public function testListaOrdensAplicandoFiltros(): void {
        $ordens = [$this->ordemServico()];

        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->expects($this->once())
            ->method('listar')
            ->with($this->callback(
                fn(FiltroOrdemServico $f) => $f->situacao === SituacaoOrdemServicoEnum::APROVADA
                    && $f->idCliente === 10
                    && $f->idVeiculo === 20,
            ))
            ->willReturn($ordens);

        $resultado = new ListarOrdensServicoUseCase($gateway)->executar(new ListarOrdensServicoInputDTO(
            situacao: SituacaoOrdemServicoEnum::APROVADA,
            idCliente: 10,
            idVeiculo: 20,
        ));

        $this->assertSame($ordens, $resultado);
    }

    private function ordemServico(): OrdemServico {
        return new OrdemServico(
            id: 1,
            idCliente: 10,
            idVeiculo: 20,
            situacao: SituacaoOrdemServicoEnum::APROVADA,
            valorTotal: 0,
            dataSolicitacao: new DateTime(),
        );
    }
}

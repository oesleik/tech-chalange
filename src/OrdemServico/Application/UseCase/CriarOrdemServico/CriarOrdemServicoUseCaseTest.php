<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Application\UseCase;

use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Application\UseCase\CriarOrdemServico\CriarOrdemServicoInputDTO;
use App\OrdemServico\Application\UseCase\CriarOrdemServico\CriarOrdemServicoUseCase;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use PHPUnit\Framework\TestCase;

final class CriarOrdemServicoUseCaseTest extends TestCase {
    public function testCriaOrdemServicoComSituacaoRecebidaEValorZerado(): void {
        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->expects($this->once())
            ->method('inserir')
            ->with($this->callback(
                fn(OrdemServico $os) => $os->idCliente() === 10
                    && $os->idVeiculo() === 20
                    && $os->situacao() === SituacaoOrdemServicoEnum::RECEBIDA
                    && $os->valorTotal() === 0.0,
            ))
            ->willReturnCallback(fn(OrdemServico $os) => $os->comId(1));

        $resultado = new CriarOrdemServicoUseCase($gateway)->executar(
            new CriarOrdemServicoInputDTO(idCliente: 10, idVeiculo: 20),
        );

        $this->assertSame(1, $resultado->id());
    }
}

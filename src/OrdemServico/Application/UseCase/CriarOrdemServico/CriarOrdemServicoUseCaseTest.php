<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Application\UseCase;

use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Application\UseCase\CriarOrdemServico\CriarOrdemServicoInputDTO;
use App\OrdemServico\Application\UseCase\CriarOrdemServico\CriarOrdemServicoUseCase;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use App\OrdemServico\Domain\Entity\ServicoOrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use PHPUnit\Framework\TestCase;

final class CriarOrdemServicoUseCaseTest extends TestCase {
    public function testCriaOrdemServicoComSituacaoRecebidaEValorZerado(): void {
        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->expects($this->once())->method('inserir')
            ->with($this->callback(fn(OrdemServico $os) => $os->idCliente() === 10
                && $os->idVeiculo() === 20
                && $os->situacao() === SituacaoOrdemServicoEnum::RECEBIDA
                && $os->valorTotal() === 0.0))
            ->willReturnCallback(fn(OrdemServico $os) => $os->comId(1));

        $itensGateway = $this->createMock(ItensOrdemServicoGatewayInterface::class);
        $itensGateway->expects($this->never())->method('adicionarPecas');
        $itensGateway->expects($this->never())->method('adicionarServicos');

        $resultado = new CriarOrdemServicoUseCase($gateway, $itensGateway)->executar(
            new CriarOrdemServicoInputDTO(idCliente: 10, idVeiculo: 20),
        );

        $this->assertSame(1, $resultado->id());
    }

    public function testAdicionaPecasEServicosInformadosNaCriacao(): void {
        $gateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $gateway->method('inserir')->willReturnCallback(fn(OrdemServico $os) => $os->comId(5));

        $itensGateway = $this->createMock(ItensOrdemServicoGatewayInterface::class);
        $itensGateway->expects($this->once())->method('adicionarPecas')->with(
            $this->callback(fn(OrdemServico $os) => $os->id() === 5),
            $this->callback(fn(array $pecas) => count($pecas) === 1
                && $pecas[0] instanceof PecaOrdemServico
                && $pecas[0]->idPeca() === 7
                && $pecas[0]->quantidade() === 2),
        );
        $itensGateway->expects($this->once())->method('adicionarServicos')->with(
            $this->callback(fn(OrdemServico $os) => $os->id() === 5),
            $this->callback(fn(array $servicos) => count($servicos) === 1
                && $servicos[0] instanceof ServicoOrdemServico
                && $servicos[0]->idServico() === 9
                && $servicos[0]->quantidade() === 3),
        );

        $resultado = new CriarOrdemServicoUseCase($gateway, $itensGateway)->executar(
            new CriarOrdemServicoInputDTO(
                idCliente: 10,
                idVeiculo: 20,
                pecas: [['id_peca' => 7, 'quantidade' => 2]],
                servicos: [['id_servico' => 9, 'quantidade' => 3]],
            ),
        );

        $this->assertSame(5, $resultado->id());
    }
}

<?php

declare(strict_types=1);

use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use App\OrdemServico\Controller\ObterOrdemServicoController;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\PecaOrdemServicoModel;
use App\OrdemServico\Model\ServicoOrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use App\OrdemServico\Service\ItensOrdemServicoService;
use App\OrdemServico\Service\OrdemServicoService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ObterOrdemServicoControllerTest extends TestCase {
    public function testObterOrdemServicoController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ObterOrdemServicoController();
        $serviceMock = $this->createMock(OrdemServicoService::class);
        $itensServiceMock = $this->createMock(ItensOrdemServicoService::class);

        $serviceMock->expects($this->exactly(1))->method("obterOrdemServicoPorId")->with(123)->willReturn(
            new OrdemServicoModel(
                id: 123,
                idCliente: 456,
                idVeiculo: 789,
                situacao: SituacaoOrdemServicoEnum::EM_DIAGNOSTICO,
                valorTotal: 10.45,
                dataSolicitacao: new DateTime(),
            )
        );

        $itensServiceMock->expects($this->exactly(1))->method("obterPecasPorIdOrdemServico")->with(123)->willReturn([
            new PecaOrdemServicoModel(
                idPeca: 111,
                quantidade: 2,
                valorUnitario: 0,
            ),
        ]);

        $itensServiceMock->expects($this->exactly(1))->method("obterServicosPorIdOrdemServico")->with(123)->willReturn([
            new ServicoOrdemServicoModel(
                idServico: 222,
                quantidade: 1,
                valorUnitario: 0,
            ),
        ]);

        $response = $controller->__invoke(
            id: 123,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
            itensService: $itensServiceMock,
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_cliente);
        $this->assertEquals(789, $res->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::EM_DIAGNOSTICO->value, $res->situacao);
        $this->assertEquals(10.45, $res->valor_total);

        $this->assertCount(1, $res->pecas);
        $this->assertCount(1, $res->servicos);

        $this->assertEquals(111, $res->pecas[0]->id_peca);
        $this->assertEquals(2, $res->pecas[0]->quantidade);

        $this->assertEquals(222, $res->servicos[0]->id_servico);
        $this->assertEquals(1, $res->servicos[0]->quantidade);
    }

    public function testObterOrdemServicoNotFound(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ObterOrdemServicoController();
        $serviceMock = $this->createMock(OrdemServicoService::class);
        $serviceMock->expects($this->exactly(1))->method("obterOrdemServicoPorId")->with(123)->willReturn(null);

        $response = $controller->__invoke(
            id: 123,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
            itensService: $this->createStub(ItensOrdemServicoService::class),
        );

        $this->assertEquals($response->getStatusCode(), 404);
    }
}

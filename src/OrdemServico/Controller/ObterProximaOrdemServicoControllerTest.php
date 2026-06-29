<?php

declare(strict_types=1);

use App\Core\Config\AppConfig;
use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use App\OrdemServico\Controller\ObterProximaOrdemServicoController;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\PecaOrdemServicoModel;
use App\OrdemServico\Model\ServicoOrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use App\OrdemServico\Service\ItensOrdemServicoService;
use App\OrdemServico\Service\OrdemServicoService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ObterProximaOrdemServicoControllerTest extends TestCase {
    public function testObterProximaOrdemServicoParaDiagnostico(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ObterProximaOrdemServicoController();
        $serviceMock = $this->createMock(OrdemServicoService::class);
        $itensServiceMock = $this->createMock(ItensOrdemServicoService::class);

        $serviceMock->expects($this->exactly(1))->method("obterProximaOrdemServicoNaFila")->willReturn(
            new OrdemServicoModel(
                id: 123,
                idCliente: 456,
                idVeiculo: 789,
                situacao: SituacaoOrdemServicoEnum::RECEBIDA,
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
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
            itensService: $itensServiceMock,
            appConfig: $container->get(AppConfig::class),
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals("realizar_diagnostico", $res->tipo_servico);

        $this->assertEquals(123, $res->ordem_servico->id);
        $this->assertEquals(456, $res->ordem_servico->id_cliente);
        $this->assertEquals(789, $res->ordem_servico->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::RECEBIDA->value, $res->ordem_servico->situacao);
        $this->assertEquals(10.45, $res->ordem_servico->valor_total);

        $this->assertCount(1, $res->ordem_servico->pecas);
        $this->assertCount(1, $res->ordem_servico->servicos);

        $this->assertEquals(111, $res->ordem_servico->pecas[0]->id_peca);
        $this->assertEquals(2, $res->ordem_servico->pecas[0]->quantidade);

        $this->assertEquals(222, $res->ordem_servico->servicos[0]->id_servico);
        $this->assertEquals(1, $res->ordem_servico->servicos[0]->quantidade);

        $this->assertEquals("marcar_em_diagnostico", $res->links[0]->rel);
        $this->assertEquals("enviar_para_aprovacao", $res->links[1]->rel);
    }

    public function testObterProximaOrdemServicoParaExecucao(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ObterProximaOrdemServicoController();
        $serviceMock = $this->createMock(OrdemServicoService::class);
        $itensServiceMock = $this->createMock(ItensOrdemServicoService::class);

        $serviceMock->expects($this->exactly(1))->method("obterProximaOrdemServicoNaFila")->willReturn(
            new OrdemServicoModel(
                id: 123,
                idCliente: 456,
                idVeiculo: 789,
                situacao: SituacaoOrdemServicoEnum::APROVADA,
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
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
            itensService: $itensServiceMock,
            appConfig: $container->get(AppConfig::class),
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals("executar_servicos", $res->tipo_servico);

        $this->assertEquals(123, $res->ordem_servico->id);
        $this->assertEquals(456, $res->ordem_servico->id_cliente);
        $this->assertEquals(789, $res->ordem_servico->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::APROVADA->value, $res->ordem_servico->situacao);
        $this->assertEquals(10.45, $res->ordem_servico->valor_total);

        $this->assertCount(1, $res->ordem_servico->pecas);
        $this->assertCount(1, $res->ordem_servico->servicos);

        $this->assertEquals(111, $res->ordem_servico->pecas[0]->id_peca);
        $this->assertEquals(2, $res->ordem_servico->pecas[0]->quantidade);

        $this->assertEquals(222, $res->ordem_servico->servicos[0]->id_servico);
        $this->assertEquals(1, $res->ordem_servico->servicos[0]->quantidade);

        $this->assertEquals("marcar_em_execucao", $res->links[0]->rel);
        $this->assertEquals("marcar_finalizada", $res->links[1]->rel);
    }

    public function testObterProximaOrdemServicoFilaVazia(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ObterProximaOrdemServicoController();
        $serviceMock = $this->createMock(OrdemServicoService::class);

        $serviceMock->expects($this->exactly(1))->method("obterProximaOrdemServicoNaFila")->willReturn(null);

        $response = $controller->__invoke(
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
            itensService: $container->get(ItensOrdemServicoService::class),
            appConfig: $this->createStub(AppConfig::class),
        );

        $this->assertEquals($response->getStatusCode(), 204);
    }
}

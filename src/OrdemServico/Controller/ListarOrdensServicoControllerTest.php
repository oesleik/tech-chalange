<?php

declare(strict_types=1);

use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use App\OrdemServico\Controller\ListarOrdensServicoController;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use App\OrdemServico\Service\OrdemServicoService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class ListarOrdensServicoControllerTest extends TestCase {
    public function testListarOrdensServicoController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ListarOrdensServicoController();
        $serviceMock = $this->createMock(OrdemServicoService::class);

        $date1 = new DateTime("2026-06-02 12:45:23");
        $date2 = new DateTime("2026-06-03 13:45:23");
        $date3 = new DateTime("2026-06-04 14:45:23");

        $serviceMock->expects($this->exactly(1))->method("listarOrdensServico")->willReturn([
            new OrdemServicoModel(
                id: 123,
                idCliente: 456,
                idVeiculo: 789,
                situacao: SituacaoOrdemServicoEnum::RECEBIDA,
                valorTotal: 45.85,
                dataSolicitacao: $date1,
            ),
            new OrdemServicoModel(
                id: 234,
                idCliente: 456,
                idVeiculo: 789,
                situacao: SituacaoOrdemServicoEnum::APROVADA,
                valorTotal: 45.85,
                dataSolicitacao: $date2,
                dataAprovacao: $date3,
            ),
        ]);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

        $response = $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());
        $this->assertCount(2, $res->ordens_servico);

        $this->assertEquals(123, $res->ordens_servico[0]->id);
        $this->assertEquals(456, $res->ordens_servico[0]->id_cliente);
        $this->assertEquals(789, $res->ordens_servico[0]->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::RECEBIDA->value, $res->ordens_servico[0]->situacao);
        $this->assertEquals(45.85, $res->ordens_servico[0]->valor_total);
        $this->assertEquals("2026-06-02 12:45:23", $res->ordens_servico[0]->data_solicitacao);
        $this->assertEquals(null, $res->ordens_servico[0]->data_aprovacao);

        $this->assertEquals(234, $res->ordens_servico[1]->id);
        $this->assertEquals(SituacaoOrdemServicoEnum::APROVADA->value, $res->ordens_servico[1]->situacao);
        $this->assertEquals("2026-06-03 13:45:23", $res->ordens_servico[1]->data_solicitacao);
        $this->assertEquals("2026-06-04 14:45:23", $res->ordens_servico[1]->data_aprovacao);
    }

    public function testListarOrdensServicoControllerInvalidFilters(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ListarOrdensServicoController();

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

        $request = $request->withQueryParams([
            "situacao" => "invalid_value",
            "id_cliente" => -1,
            "id_veiculo" => -1,
        ]);

        $response = $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $this->createStub(OrdemServicoService::class),
        );

        $this->assertEquals($response->getStatusCode(), 400);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());
        $this->assertStringContainsString("situacao", $res->errors[0]->field);
        $this->assertStringContainsString("id_cliente", $res->errors[1]->field);
        $this->assertStringContainsString("id_veiculo", $res->errors[2]->field);
    }
}

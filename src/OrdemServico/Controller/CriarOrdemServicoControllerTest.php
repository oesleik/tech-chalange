<?php

declare(strict_types=1);

use App\OrdemServico\Controller\CriarOrdemServicoController;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Service\OrdemServicoService;
use App\Core\Contract\ContractResolver;
use App\Core\Database\DatabaseErrorEnum;
use App\Core\ServiceContainerBuilder;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class CriarOrdemServicoControllerTest extends TestCase {
    public function testCriarOrdemServicoController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarOrdemServicoController();
        $serviceMock = $this->createMock(OrdemServicoService::class);

        $serviceMock->expects($this->exactly(1))->method("criarOrdemServico")->withAnyParameters()->willReturn(
            new OrdemServicoModel(
                id: 123,
                idCliente: 456,
                idVeiculo: 789,
                situacao: SituacaoOrdemServicoEnum::RECEBIDA,
                valorTotal: 45.85,
                dataSolicitacao: new DateTime("2026-06-02 12:45:23"),
            )
        );

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

        $request->getBody()->write(json_encode([
            "id_cliente" => 456,
            "id_veiculo" => 789,
        ]));

        $request->getBody()->rewind();

        $response = $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 201);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_cliente);
        $this->assertEquals(789, $res->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::RECEBIDA->value, $res->situacao);
        $this->assertEquals(45.85, $res->valor_total);
        $this->assertEquals("2026-06-02 12:45:23", $res->data_solicitacao);
        $this->assertEquals(null, $res->data_aprovacao);
    }

    public function testCriarOrdemServicoControllerInvalidInput(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarOrdemServicoController();
        $serviceMock = $this->createMock(OrdemServicoService::class);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

        $request->getBody()->write(json_encode([
            "id_cliente" => -1,
            "id_veiculo" => -1,
        ]));

        $request->getBody()->rewind();

        $response = $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 400);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());
        $this->assertStringContainsString("id_cliente", $res->errors[0]->field);
        $this->assertStringContainsString("id_veiculo", $res->errors[1]->field);
    }
}

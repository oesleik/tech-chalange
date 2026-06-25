<?php

declare(strict_types=1);

use App\Servicos\Controller\EditarServicoController;
use App\Servicos\Model\ServicoModel;
use App\Servicos\Service\ServicosService;
use App\Core\Contract\ContractResolver;
use App\Core\Database\DatabaseErrorEnum;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class EditarServicoControllerTest extends TestCase {
    public function testEditarServicoController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new EditarServicoController();
        $serviceMock = $this->createMock(ServicosService::class);

        $serviceMock->expects($this->exactly(1))->method("obterServicoPorId")->with(123)->willReturn(
            new ServicoModel(
                id: 123,
                descricao: "Revisão",
                valorUnitario: 145,
            )
        );

        $serviceMock->expects($this->exactly(1))->method("atualizarServico");

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/servicos/123");

        $request->getBody()->write(json_encode([
            "descricao" => "Diagnóstico",
            "valor_unitario" => 80,
        ]));

        $request->getBody()->rewind();

        $response = $controller->__invoke(
            id: 123,
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals("Diagnóstico", $res->descricao);
        $this->assertEquals(80, $res->valor_unitario);
    }

    public function testEditarServicoControllerNotFound(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new EditarServicoController();
        $serviceMock = $this->createMock(ServicosService::class);
        $serviceMock->expects($this->exactly(1))->method("obterServicoPorId")->with(123)->willReturn(null);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/servicos/123");

        $request->getBody()->write(json_encode([
            "descricao" => "Diagnóstico",
            "valor_unitario" => 80,
        ]));

        $request->getBody()->rewind();

        $response = $controller->__invoke(
            id: 123,
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testEditarServicoControllerInvalidInput(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new EditarServicoController();
        $serviceMock = $this->createMock(ServicosService::class);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/servicos/123");

        $request->getBody()->write(json_encode([
            "valor_unitario" => -1,
        ]));

        $request->getBody()->rewind();

        $response = $controller->__invoke(
            id: 123,
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 400);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());
        $this->assertStringContainsString("valor_unitario", $res->errors[0]->field);
    }

}

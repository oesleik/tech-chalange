<?php

declare(strict_types=1);

use App\Peca\Controller\EditarPecaController;
use App\Peca\Model\PecaModel;
use App\Peca\Service\PecaService;
use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class EditarPecaControllerTest extends TestCase {
    public function testEditarPecaController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new EditarPecaController();
        $serviceMock = $this->createMock(PecaService::class);

        $serviceMock->expects($this->exactly(1))->method("obterPecaPorId")->with(123)->willReturn(
            new PecaModel(
                id: 123,
                descricao: "Vela",
                valorUnitario: 22.45
            )
        );

        $serviceMock->expects($this->exactly(1))->method("atualizarPeca");

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/pecas/123");

        $request->getBody()->write(json_encode([
            "descricao" => "Correia",
            "valor_unitario" => 245,
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
        $this->assertEquals("Correia", $res->descricao);
        $this->assertEquals("245,00", $res->valor_unitario);
    }

    public function testEditarPecaControllerNotFound(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new EditarPecaController();
        $serviceMock = $this->createMock(PecaService::class);
        $serviceMock->expects($this->exactly(1))->method("obterPecaPorId")->with(123)->willReturn(null);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/pecas/123");

        $request->getBody()->write(json_encode([
            "descricao" => "Correia",
            "valor_unitario" => 245,
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

    public function testEditarPecaControllerInvalidInput(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new EditarPecaController();
        $serviceMock = $this->createMock(PecaService::class);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/pecas/123");

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

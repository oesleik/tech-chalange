<?php

declare(strict_types=1);

use App\Peca\Controller\CriarPecaController;
use App\Peca\Model\PecaModel;
use App\Peca\Service\PecaService;
use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class CriarPecaControllerTest extends TestCase {
    public function testCriarPecaController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarPecaController();
        $serviceMock = $this->createMock(PecaService::class);

        $serviceMock->expects($this->exactly(1))->method("criarPeca")->withAnyParameters()->willReturn(
            new PecaModel(
                id: 123,
                descricao: "Vela",
                valorUnitario: 22.45
            )
        );

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/pecas/");

        $request->getBody()->write(json_encode([
            "descricao" => "Vela",
            "valor_unitario" => 22.45,
        ]));

        $request->getBody()->rewind();

        $response = $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals("Vela", $res->descricao);
        $this->assertEquals("22,45", $res->valor_unitario);
    }

    public function testCriarPecaControllerInvalidInput(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarPecaController();
        $serviceMock = $this->createMock(PecaService::class);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/pecas/");

        $request->getBody()->write(json_encode([
            "descricao" => "",
            "valor_unitario" => -1,
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
        $this->assertStringContainsString("descricao", $res->errors[0]->field);
        $this->assertStringContainsString("valor_unitario", $res->errors[1]->field);
    }

}

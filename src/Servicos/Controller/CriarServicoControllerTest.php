<?php

declare(strict_types=1);

use App\Servicos\Controller\CriarServicoController;
use App\Servicos\Model\ServicoModel;
use App\Servicos\Service\ServicosService;
use App\Core\Contract\ContractResolver;
use App\Core\Database\DatabaseErrorEnum;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class CriarServicoControllerTest extends TestCase {
    public function testCriarServicoController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarServicoController();
        $serviceMock = $this->createMock(ServicosService::class);

        $serviceMock->expects($this->exactly(1))->method("criarServico")->withAnyParameters()->willReturn(
            new ServicoModel(
                id: 123,
                descricao: "Revisão",
                valorUnitario: 145,
            )
        );

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/servicos/");

        $request->getBody()->write(json_encode([
            "descricao" => "Revisão",
            "valor_unitario" => 145,
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
        $this->assertEquals("Revisão", $res->descricao);
        $this->assertEquals(145, $res->valor_unitario);
    }

    public function testCriarServicoControllerInvalidInput(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarServicoController();
        $serviceMock = $this->createMock(ServicosService::class);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/servicos/");

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

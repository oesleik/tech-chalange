<?php

declare(strict_types=1);

use App\Veiculos\Controller\CriarVeiculoController;
use App\Veiculos\Model\VeiculoModel;
use App\Veiculos\Service\VeiculoService;
use App\Core\Contract\ContractResolver;
use App\Core\Database\DatabaseErrorEnum;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class CriarVeiculoControllerTest extends TestCase {
    public function testCriarVeiculoController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarVeiculoController();
        $serviceMock = $this->createMock(VeiculoService::class);

        $serviceMock->expects($this->exactly(1))->method("criarVeiculo")->withAnyParameters()->willReturn(
            new VeiculoModel(
                id: 123,
                placa: "ABC-1234",
                marca: "Volkswagen",
                modelo: "Gol",
            )
        );

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/veiculos/");

        $request->getBody()->write(json_encode([
            "placa" => "ABC-1234",
            "marca" => "Volkswagen",
            "modelo" => "Gol",
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
        $this->assertEquals("ABC-1234", $res->placa);
        $this->assertEquals("Volkswagen", $res->marca);
        $this->assertEquals("Gol", $res->modelo);
    }

    public function testCriarVeiculoControllerDuplicated(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarVeiculoController();
        $serviceMock = $this->createMock(VeiculoService::class);

        $exception = new PDOException();
        $exception->errorInfo = [0, DatabaseErrorEnum::DUPLICATE_ENTRY->value];
        $serviceMock->expects($this->exactly(1))->method("criarVeiculo")->withAnyParameters()->willThrowException($exception);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/veiculos/");

        $request->getBody()->write(json_encode([
            "placa" => "ABC-1234",
            "marca" => "Volkswagen",
            "modelo" => "Gol",
        ]));

        $request->getBody()->rewind();

        $response = $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 409);
    }

    public function testCriarVeiculoControllerInvalidInput(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarVeiculoController();
        $serviceMock = $this->createMock(VeiculoService::class);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/veiculos/");

        $request->getBody()->write(json_encode([
            "placa" => "ABC",
            "marca" => "",
            "modelo" => "",
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
        $this->assertStringContainsString("placa", $res->errors[0]->field);
        $this->assertStringContainsString("marca", $res->errors[1]->field);
        $this->assertStringContainsString("modelo", $res->errors[2]->field);
    }

    public function testCriarVeiculoControllerDatabaseError(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarVeiculoController();
        $serviceMock = $this->createMock(VeiculoService::class);

        $exception = new PDOException();
        $exception->errorInfo = [0, DatabaseErrorEnum::LOCK_WAIT_TIMEOUT->value];
        $serviceMock->expects($this->exactly(1))->method("criarVeiculo")->withAnyParameters()->willThrowException($exception);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/veiculos/");

        $request->getBody()->write(json_encode([
            "placa" => "ABC-1234",
            "marca" => "Volkswagen",
            "modelo" => "Gol",
        ]));

        $request->getBody()->rewind();
        $this->expectException(PDOException::class);

        $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );
    }

}

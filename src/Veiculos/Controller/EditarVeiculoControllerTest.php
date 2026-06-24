<?php

declare(strict_types=1);

use App\Veiculos\Controller\EditarVeiculoController;
use App\Veiculos\Model\VeiculoModel;
use App\Veiculos\Service\VeiculoService;
use App\Core\Contract\ContractResolver;
use App\Core\Database\DatabaseErrorEnum;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class EditarVeiculoControllerTest extends TestCase {
    public function testEditarVeiculoController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new EditarVeiculoController();
        $serviceMock = $this->createMock(VeiculoService::class);

        $serviceMock->expects($this->exactly(1))->method("obterVeiculoPorId")->with(123)->willReturn(
            new VeiculoModel(
                id: 123,
                placa: "ABC-1234",
				marca: "Volkswagen",
				modelo: "Gol",
            )
        );

        $serviceMock->expects($this->exactly(1))->method("atualizarVeiculo");

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/veiculos/123");

        $request->getBody()->write(json_encode([
            "placa" => "AAA-4D56",
            "marca" => "Fiat",
            "modelo" => "Uno",
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
        $this->assertEquals("AAA-4D56", $res->placa);
        $this->assertEquals("Fiat", $res->marca);
        $this->assertEquals("Uno", $res->modelo);
    }

    public function testEditarVeiculoControllerDuplicated(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new EditarVeiculoController();
        $serviceMock = $this->createMock(VeiculoService::class);

        $exception = new PDOException();
        $exception->errorInfo = [0, DatabaseErrorEnum::DUPLICATE_ENTRY->value];

        $serviceMock->expects($this->exactly(1))->method("obterVeiculoPorId")->with(123)->willReturn(
            new VeiculoModel(
                id: 123,
                placa: "ABC-1234",
				marca: "Volkswagen",
				modelo: "Gol",
            )
        );
        $serviceMock->expects($this->exactly(1))->method("atualizarVeiculo")->willThrowException($exception);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/veiculos/123");

        $request->getBody()->write(json_encode([
            "placa" => "AAA-4D56",
            "marca" => "Fiat",
            "modelo" => "Uno",
        ]));

        $request->getBody()->rewind();

        $response = $controller->__invoke(
            id: 123,
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 409);
    }

    public function testEditarVeiculoControllerNotFound(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new EditarVeiculoController();
        $serviceMock = $this->createMock(VeiculoService::class);

        $exception = new PDOException();
        $exception->errorInfo = [0, DatabaseErrorEnum::DUPLICATE_ENTRY->value];

        $serviceMock->expects($this->exactly(1))->method("obterVeiculoPorId")->with(123)->willReturn(null);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/veiculos/123");

        $request->getBody()->write(json_encode([
            "placa" => "AAA-4D56",
            "marca" => "Fiat",
            "modelo" => "Uno",
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

    public function testEditarVeiculoControllerInvalidInput(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new EditarVeiculoController();
        $serviceMock = $this->createMock(VeiculoService::class);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/veiculos/123");

        $request->getBody()->write(json_encode([
            "placa" => "AAA",
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
        $this->assertStringContainsString("placa", $res->errors[0]->field);
    }

    public function testEditarVeiculoControllerDatabaseError(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new EditarVeiculoController();
        $serviceMock = $this->createMock(VeiculoService::class);

        $exception = new PDOException();
        $exception->errorInfo = [0, DatabaseErrorEnum::LOCK_WAIT_TIMEOUT->value];

        $serviceMock->expects($this->exactly(1))->method("obterVeiculoPorId")->with(123)->willReturn(
            new VeiculoModel(
                id: 123,
                placa: "ABC-1234",
				marca: "Volkswagen",
				modelo: "Gol",
            )
        );

        $serviceMock->expects($this->exactly(1))->method("atualizarVeiculo")->withAnyParameters()->willThrowException($exception);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/veiculos/123");

        $request->getBody()->write(json_encode([
            "placa" => "AAA-4D56",
            "marca" => "Fiat",
            "modelo" => "Uno",
        ]));

        $request->getBody()->rewind();
        $this->expectException(PDOException::class);

        $controller->__invoke(
            id: 123,
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );
    }

}

<?php

declare(strict_types=1);

use App\Clientes\Controller\CriarClienteController;
use App\Clientes\Model\ClienteModel;
use App\Clientes\Service\ClienteService;
use App\Clientes\ValueObject\CpfValue;
use App\Clientes\ValueObject\EmailValue;
use App\Clientes\ValueObject\TelefoneValue;
use App\Core\Contract\ContractResolver;
use App\Core\Database\DatabaseErrorEnum;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class CriarClienteControllerTest extends TestCase {
    public function testCriarClienteController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarClienteController();
        $serviceMock = $this->createMock(ClienteService::class);

        $serviceMock->expects($this->exactly(1))->method("criarCliente")->withAnyParameters()->willReturn(
            new ClienteModel(
                id: 123,
                nome: "Fulano de Tal",
                cpfCnpj: new CpfValue("52998224725"),
                email: new EmailValue("fulano@gmail.com"),
                telefone: new TelefoneValue("54999999999"),
            )
        );

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/clientes/");

        $request->getBody()->write(json_encode([
            "nome" => "Fulano de Tal",
            "cpf_cnpj" => "52998224725",
            "email" => "fulano@gmail.com",
            "telefone" => "54999999999",
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
        $this->assertEquals("Fulano de Tal", $res->nome);
        $this->assertEquals("52*.***.***-25", $res->cpf_cnpj);
        $this->assertEquals("fu****@gmail.com", $res->email);
        $this->assertEquals("*********99", $res->telefone);
    }

    public function testCriarClienteControllerDuplicated(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarClienteController();
        $serviceMock = $this->createMock(ClienteService::class);

        $exception = new PDOException();
        $exception->errorInfo = [0, DatabaseErrorEnum::DUPLICATE_ENTRY->value];
        $serviceMock->expects($this->exactly(1))->method("criarCliente")->withAnyParameters()->willThrowException($exception);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/clientes/");

        $request->getBody()->write(json_encode([
            "nome" => "Fulano de Tal",
            "cpf_cnpj" => "52998224725",
            "email" => "fulano@gmail.com",
            "telefone" => "54999999999",
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

    public function testCriarClienteControllerInvalidInput(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarClienteController();
        $serviceMock = $this->createMock(ClienteService::class);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/clientes/");

        $request->getBody()->write(json_encode([
            "nome" => "Fulano de Tal",
            "cpf_cnpj" => "52998224726",
            "email" => "fulano",
            "telefone" => "sdf",
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
        $this->assertStringContainsString("cpf_cnpj", $res->errors[0]->field);
        $this->assertStringContainsString("email", $res->errors[1]->field);
        $this->assertStringContainsString("telefone", $res->errors[2]->field);
    }

    public function testCriarClienteControllerDatabaseError(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new CriarClienteController();
        $serviceMock = $this->createMock(ClienteService::class);

        $exception = new PDOException();
        $exception->errorInfo = [0, DatabaseErrorEnum::LOCK_WAIT_TIMEOUT->value];
        $serviceMock->expects($this->exactly(1))->method("criarCliente")->withAnyParameters()->willThrowException($exception);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/clientes/");

        $request->getBody()->write(json_encode([
            "nome" => "Fulano de Tal",
            "cpf_cnpj" => "52998224725",
            "email" => "fulano@gmail.com",
            "telefone" => "54999999999",
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

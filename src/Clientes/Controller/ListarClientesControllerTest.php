<?php

declare(strict_types=1);

use App\Clientes\Controller\ListarClientesController;
use App\Clientes\Model\ClienteModel;
use App\Clientes\Service\ClienteService;
use App\Clientes\ValueObject\CpfValue;
use App\Clientes\ValueObject\EmailValue;
use App\Clientes\ValueObject\TelefoneValue;
use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class ListarClientesControllerTest extends TestCase {
    public function testListarClientesController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ListarClientesController();
        $serviceMock = $this->createMock(ClienteService::class);

        $serviceMock->expects($this->exactly(1))->method("listarClientes")->with(null)->willReturn([
            new ClienteModel(
                id: 123,
                nome: "Fulano de Tal",
                cpfCnpj: new CpfValue("52998224725"),
                email: new EmailValue("fulano@gmail.com"),
                telefone: new TelefoneValue("54999999999")
            ),
        ]);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("GET", "/clientes/");

        $response = $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());
        $this->assertCount(1, $res->clientes);

        $res = $res->clientes[0];
        $this->assertEquals(123, $res->id);
        $this->assertEquals("Fulano de Tal", $res->nome);
        $this->assertEquals("52*.***.***-25", $res->cpf_cnpj);
        $this->assertEquals("fu****@gmail.com", $res->email);
        $this->assertEquals("*********99", $res->telefone);
    }

    public function testListarClientesControllerComFiltro(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ListarClientesController();
        $serviceMock = $this->createMock(ClienteService::class);

        $cpfFilter = new CpfValue("52998224725");

        $serviceMock->expects($this->exactly(1))->method("listarClientes")->with($cpfFilter)->willReturn([]);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("GET", "/clientes/");
        $request = $request->withQueryParams(["cpf_cnpj" => $cpfFilter->getValue()]);

        $response = $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());
        $this->assertCount(0, $res->clientes);
    }

}

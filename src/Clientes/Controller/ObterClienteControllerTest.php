<?php

declare(strict_types=1);

use App\Clientes\Controller\ObterClienteController;
use App\Clientes\Model\ClienteModel;
use App\Clientes\Service\ClienteService;
use App\Clientes\ValueObject\CpfValue;
use App\Clientes\ValueObject\EmailValue;
use App\Clientes\ValueObject\TelefoneValue;
use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ObterClienteControllerTest extends TestCase {
    public function testObterClienteController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ObterClienteController();
        $serviceStub = $this->createStub(ClienteService::class);

        $serviceStub->method("obterClientePorId")->willReturn(
            new ClienteModel(
                id: 123,
                nome: "Fulano de Tal",
                cpfCnpj: new CpfValue("52998224725"),
                email: new EmailValue("fulano@gmail.com"),
                telefone: new TelefoneValue("54999999999")
            )
        );

        $response = $controller->__invoke(
            id: 123,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceStub,
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals("Fulano de Tal", $res->nome);
        $this->assertEquals("529.982.247-25", $res->cpf_cnpj);
        $this->assertEquals("fulano@gmail.com", $res->email);
        $this->assertEquals("54999999999", $res->telefone);
    }

    public function testObterClienteControllerNotFound(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ObterClienteController();
        $serviceStub = $this->createStub(ClienteService::class);
        $serviceStub->method("obterClientePorId")->willReturn(null);

        $response = $controller->__invoke(
            id: 123,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceStub,
        );

        $this->assertEquals($response->getStatusCode(), 404);

        $response->getBody()->rewind();
        $this->assertEmpty($response->getBody()->getContents());
    }

}

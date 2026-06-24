<?php

declare(strict_types=1);

use App\Veiculos\Controller\ListarVeiculosController;
use App\Veiculos\Model\VeiculoModel;
use App\Veiculos\Service\VeiculoService;
use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ListarVeiculosControllerTest extends TestCase {
    public function testListarVeiculosController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ListarVeiculosController();
        $serviceMock = $this->createMock(VeiculoService::class);

        $serviceMock->expects($this->exactly(1))->method("listarVeiculos")->willReturn([
            new VeiculoModel(
                id: 123,
                placa: "ABC-1234",
                marca: "Volkswagen",
                modelo: "Gol",
            ),
        ]);

        $response = $controller->__invoke(
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());
        $this->assertCount(1, $res->veiculos);

        $res = $res->veiculos[0];
        $this->assertEquals(123, $res->id);
        $this->assertEquals("ABC-1234", $res->placa);
        $this->assertEquals("Volkswagen", $res->marca);
        $this->assertEquals("Gol", $res->modelo);
    }

}

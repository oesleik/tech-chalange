<?php

declare(strict_types=1);

use App\Servicos\Controller\ListarServicosController;
use App\Servicos\Model\ServicoModel;
use App\Servicos\Service\ServicosService;
use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ListarServicosControllerTest extends TestCase {
    public function testListarServicosController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ListarServicosController();
        $serviceMock = $this->createMock(ServicosService::class);

        $serviceMock->expects($this->exactly(1))->method("listarServicos")->willReturn([
            new ServicoModel(
                id: 123,
                descricao: "Revisão",
                valorUnitario: 150,
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
        $this->assertCount(1, $res->servicos);

        $res = $res->servicos[0];
        $this->assertEquals(123, $res->id);
        $this->assertEquals("Revisão", $res->descricao);
        $this->assertEquals(150, $res->valor_unitario);
    }

}

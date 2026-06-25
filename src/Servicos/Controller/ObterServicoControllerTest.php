<?php

declare(strict_types=1);

use App\Servicos\Controller\ObterServicoController;
use App\Servicos\Model\ServicoModel;
use App\Servicos\Service\ServicosService;
use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ObterServicoControllerTest extends TestCase {
    public function testObterServicoController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ObterServicoController();
        $serviceMock = $this->createMock(ServicosService::class);

        $serviceMock->expects($this->exactly(1))->method("obterServicoPorId")->with(123)->willReturn(
            new ServicoModel(
                id: 123,
                descricao: "Revisão",
                valorUnitario: 150,
            )
        );

        $response = $controller->__invoke(
            id: 123,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals("Revisão", $res->descricao);
        $this->assertEquals(150, $res->valor_unitario);
    }

    public function testObterServicoControllerNotFound(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ObterServicoController();
        $serviceMock = $this->createMock(ServicosService::class);
        $serviceMock->expects($this->exactly(1))->method("obterServicoPorId")->with(123)->willReturn(null);

        $response = $controller->__invoke(
            id: 123,
            response: $container->get(ResponseInterface::class),
            contractResolver: $container->get(ContractResolver::class),
            service: $serviceMock,
        );

        $this->assertEquals($response->getStatusCode(), 404);

        $response->getBody()->rewind();
        $this->assertEmpty($response->getBody()->getContents());
    }

}

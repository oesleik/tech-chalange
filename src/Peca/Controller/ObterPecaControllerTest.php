<?php

declare(strict_types=1);

use App\Peca\Controller\ObterPecaController;
use App\Peca\Model\PecaModel;
use App\Peca\Service\PecaService;
use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ObterPecaControllerTest extends TestCase {
    public function testObterPecaController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ObterPecaController();
        $serviceMock = $this->createMock(PecaService::class);

        $serviceMock->expects($this->exactly(1))->method("obterPecaPorId")->with(123)->willReturn(
            new PecaModel(
                id: 123,
                descricao: "Vela",
                valorUnitario: 22.45
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
        $this->assertEquals("Vela", $res->descricao);
        $this->assertEquals("22,45", $res->valor_unitario);
    }

    public function testObterPecaControllerNotFound(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ObterPecaController();
        $serviceMock = $this->createMock(PecaService::class);
        $serviceMock->expects($this->exactly(1))->method("obterPecaPorId")->with(123)->willReturn(null);

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

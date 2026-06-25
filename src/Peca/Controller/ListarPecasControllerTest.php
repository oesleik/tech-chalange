<?php

declare(strict_types=1);

use App\Peca\Controller\ListarPecasController;
use App\Peca\Model\PecaModel;
use App\Peca\Service\PecaService;
use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ListarPecasControllerTest extends TestCase {
    public function testListarPecasController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $controller = new ListarPecasController();
        $serviceMock = $this->createMock(PecaService::class);

        $serviceMock->expects($this->exactly(1))->method("listarPecas")->willReturn([
            new PecaModel(
                id: 123,
                descricao: "Vela",
                valorUnitario: 22.45
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
        $this->assertCount(1, $res->pecas);

        $res = $res->pecas[0];
        $this->assertEquals(123, $res->id);
        $this->assertEquals("Vela", $res->descricao);
        $this->assertEquals("22,45", $res->valor_unitario);
    }

}

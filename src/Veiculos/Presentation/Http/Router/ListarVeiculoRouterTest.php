<?php

declare(strict_types=1);

namespace Tests\Veiculos\Presentation\Http\Router;

use App\Core\Presentation\Http\PresenterInterface;
use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoOutputDTO;
use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoUseCase;
use App\Veiculos\Presentation\Http\Controller\ListarVeiculoController;
use App\Veiculos\Presentation\Http\Router\ListarVeiculoRouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListarVeiculoRouterTest extends TestCase {
    public function testInvokeDelegaParaOControllerERetornaAResposta(): void {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);

        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(ListarVeiculoUseCase::class);
        $useCase->expects($this->once())
            ->method('executar')
            ->willReturn(new ListarVeiculoOutputDTO(veiculos: [], total: 0, pagina: 1, porPagina: 20));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('success')->willReturn($response);

        $controller = new ListarVeiculoController($useCase, $presenter);
        $router = new ListarVeiculoRouter($controller);

        $resultado = $router($request, $response);

        $this->assertSame($response, $resultado);
    }
}

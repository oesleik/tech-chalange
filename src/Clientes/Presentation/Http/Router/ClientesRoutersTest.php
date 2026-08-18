<?php

declare(strict_types=1);

namespace Tests\Clientes\Presentation\Http\Router;

use App\Clientes\Presentation\Http\Controller\CriarClienteControllerInterface;
use App\Clientes\Presentation\Http\Controller\EditarClienteControllerInterface;
use App\Clientes\Presentation\Http\Controller\ListarClientesControllerInterface;
use App\Clientes\Presentation\Http\Controller\ObterClienteControllerInterface;
use App\Clientes\Presentation\Http\Router\CriarClienteRouter;
use App\Clientes\Presentation\Http\Router\EditarClienteRouter;
use App\Clientes\Presentation\Http\Router\ListarClientesRouter;
use App\Clientes\Presentation\Http\Router\ObterClienteRouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ClientesRoutersTest extends TestCase {
    public function testCriarClienteRouterDelegaParaController(): void {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $controller = $this->createMock(CriarClienteControllerInterface::class);
        $controller->expects($this->once())->method('execute')->with($request, $response)->willReturn($response);

        $this->assertSame($response, new CriarClienteRouter($controller)($request, $response));
    }

    public function testEditarClienteRouterDelegaParaController(): void {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $controller = $this->createMock(EditarClienteControllerInterface::class);
        $controller->expects($this->once())->method('execute')->with(7, $request, $response)->willReturn($response);

        $this->assertSame($response, new EditarClienteRouter($controller)(7, $request, $response));
    }

    public function testListarClientesRouterDelegaParaController(): void {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $controller = $this->createMock(ListarClientesControllerInterface::class);
        $controller->expects($this->once())->method('execute')->with($request, $response)->willReturn($response);

        $this->assertSame($response, new ListarClientesRouter($controller)($request, $response));
    }

    public function testObterClienteRouterDelegaParaController(): void {
        $response = $this->createMock(ResponseInterface::class);
        $controller = $this->createMock(ObterClienteControllerInterface::class);
        $controller->expects($this->once())->method('execute')->with(7, $response)->willReturn($response);

        $this->assertSame($response, new ObterClienteRouter($controller)(7, $response));
    }
}

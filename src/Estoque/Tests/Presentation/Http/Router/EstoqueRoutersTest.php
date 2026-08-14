<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Presentation\Http\Router;

use App\Estoque\Presentation\Http\Controller\ConsultarEstoquePorPecaController;
use App\Estoque\Presentation\Http\Controller\RegistrarBaixaEstoqueController;
use App\Estoque\Presentation\Http\Controller\RegistrarEntradaEstoqueController;
use App\Estoque\Presentation\Http\Router\ConsultarEstoquePorPecaRouter;
use App\Estoque\Presentation\Http\Router\RegistrarBaixaEstoqueRouter;
use App\Estoque\Presentation\Http\Router\RegistrarEntradaEstoqueRouter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Estoque\Presentation\Http\Controller\ConsultarEstoquePorPecaControllerInterface;
use App\Estoque\Presentation\Http\Controller\RegistrarBaixaEstoqueControllerInterface;
use App\Estoque\Presentation\Http\Controller\RegistrarEntradaEstoqueControllerInterface;

final class EstoqueRoutersTest extends TestCase {
    public function testEntradaRouterDelegaParaController(): void {
        $request    = $this->createMock(ServerRequestInterface::class);
        $response   = $this->createMock(ResponseInterface::class);
        $controller = $this->createMock(RegistrarEntradaEstoqueControllerInterface::class);

        $controller
            ->expects($this->once())
            ->method('execute')
            ->with($request, $response)
            ->willReturn($response);

        $router = new RegistrarEntradaEstoqueRouter($controller);
        $result = $router($request, $response);

        $this->assertSame($response, $result);
    }

    public function testBaixaRouterDelegaParaController(): void {
        $request    = $this->createMock(ServerRequestInterface::class);
        $response   = $this->createMock(ResponseInterface::class);
        $controller = $this->createMock(RegistrarBaixaEstoqueControllerInterface::class);

        $controller
            ->expects($this->once())
            ->method('execute')
            ->with($request, $response)
            ->willReturn($response);

        $router = new RegistrarBaixaEstoqueRouter($controller);
        $result = $router($request, $response);

        $this->assertSame($response, $result);
    }

    public function testConsultarRouterDelegaParaController(): void {
        $request    = $this->createMock(ServerRequestInterface::class);
        $response   = $this->createMock(ResponseInterface::class);
        $args       = ['id' => '1'];
        $controller = $this->createMock(ConsultarEstoquePorPecaControllerInterface::class);

        $controller
            ->expects($this->once())
            ->method('execute')
            ->with($request, $response, $args)
            ->willReturn($response);

        $router = new ConsultarEstoquePorPecaRouter($controller);
        $result = $router($request, $response, $args);

        $this->assertSame($response, $result);
    }
}

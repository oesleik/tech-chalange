<?php

declare(strict_types=1);

namespace Tests\Peca\Presentation\Http\Router;

use App\Peca\Application\UseCase\ListarPeca\ListarPecaUseCase;
use App\Peca\Presentation\Http\Controller\ListarPecasController;
use App\Peca\Presentation\Http\Router\ListarPecasRouter;
use App\Core\Contract\ContractResolver;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class ListarPecasRouterTest extends TestCase {
    public function testInvokeDelegaParaOControllerERetornaAResposta(): void {
        $request = $this->createMock(ServerRequestInterface::class);

        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->method('write');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($responseBody);
        $response->method('withHeader')->willReturnSelf();

        $useCase = $this->createMock(ListarPecaUseCase::class);
        $useCase->expects($this->once())->method('executar')->willReturn([]);

        $contractResolver = $this->createMock(ContractResolver::class);
        $contractResolver->method('toJson')->willReturnCallback(fn(object $dto) => json_encode($dto));

        $controller = new ListarPecasController($useCase, $contractResolver);
        $router = new ListarPecasRouter($controller);

        $resultado = $router($request, $response);

        $this->assertSame($response, $resultado);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Peca\Presentation\Http\Router;

use App\Peca\Application\UseCase\CriarPeca\CriarPecaUseCase;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\ValueObject\ValorUnitario;
use App\Peca\Presentation\Http\Controller\CriarPecaController;
use App\Peca\Presentation\Http\Router\CriarPecaRouter;
use App\Core\Contract\ContractResolver;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class CriarPecaRouterTest extends TestCase {
    public function testInvokeDelegaParaOControllerERetornaAResposta(): void {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode([
            'descricao' => 'Filtro de óleo',
            'valor_unitario' => 49.90,
        ]));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($stream);

        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->method('write');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($responseBody);
        $response->method('withHeader')->willReturnSelf();

        $pecaCriada = Peca::reconstituir(1, 'Filtro de óleo', new ValorUnitario(49.90));

        $useCase = $this->createMock(CriarPecaUseCase::class);
        $useCase->expects($this->once())->method('executar')->willReturn($pecaCriada);

        $contractResolver = $this->createMock(ContractResolver::class);
        $contractResolver->method('toJson')->willReturnCallback(fn(object $dto) => json_encode($dto));

        $controller = new CriarPecaController($useCase, $contractResolver);
        $router = new CriarPecaRouter($controller);

        $resultado = $router($request, $response);

        $this->assertSame($response, $resultado);
    }
}

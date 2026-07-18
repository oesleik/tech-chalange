<?php

declare(strict_types=1);

namespace Tests\Peca\Presentation\Http\Router;

use App\Peca\Application\UseCase\EditarPeca\EditarPecaUseCase;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\ValueObject\ValorUnitario;
use App\Peca\Presentation\Http\Controller\EditarPecaController;
use App\Peca\Presentation\Http\Router\EditarPecaRouter;
use App\Core\Contract\ContractResolver;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class EditarPecaRouterTest extends TestCase {
    public function testInvokeDelegaParaOControllerComIdERetornaAResposta(): void {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode([
            'descricao' => 'Filtro de óleo premium',
        ]));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($stream);

        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->method('write');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($responseBody);
        $response->method('withHeader')->willReturnSelf();
        $response->method('withStatus')->willReturnSelf();

        $pecaAtualizada = Peca::reconstituir(1, 'Filtro de óleo premium', new ValorUnitario(49.90));

        $useCase = $this->createMock(EditarPecaUseCase::class);
        $useCase->expects($this->once())->method('executar')->with(1)->willReturn($pecaAtualizada);

        $contractResolver = $this->createMock(ContractResolver::class);
        $contractResolver->method('toJson')->willReturnCallback(fn(object $dto) => json_encode($dto));

        $controller = new EditarPecaController($useCase, $contractResolver);
        $router = new EditarPecaRouter($controller);

        $resultado = $router($request, $response, 1);

        $this->assertSame($response, $resultado);
    }
}

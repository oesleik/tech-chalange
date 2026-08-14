<?php

declare(strict_types=1);

namespace Tests\Peca\Presentation\Http\Router;

use App\Peca\Application\UseCase\ObterPeca\ObterPecaUseCase;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\ValueObject\ValorUnitario;
use App\Peca\Presentation\Http\Controller\ObterPecaController;
use App\Peca\Presentation\Http\Router\ObterPecaRouter;
use App\Core\Contract\ContractResolver;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class ObterPecaRouterTest extends TestCase {
    public function testInvokeDelegaParaOControllerComIdERetornaAResposta(): void {
        $request = $this->createMock(ServerRequestInterface::class);

        $responseBody = $this->createMock(StreamInterface::class);
        $responseBody->method('write');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($responseBody);
        $response->method('withHeader')->willReturnSelf();

        $peca = Peca::reconstituir(1, 'Filtro de óleo', new ValorUnitario(49.90));

        $useCase = $this->createMock(ObterPecaUseCase::class);
        $useCase->expects($this->once())->method('executar')->with(1)->willReturn($peca);

        $contractResolver = $this->createMock(ContractResolver::class);
        $contractResolver->method('toJson')->willReturnCallback(fn(object $dto) => json_encode($dto));

        $controller = new ObterPecaController($useCase, $contractResolver);
        $router = new ObterPecaRouter($controller);

        $resultado = $router($request, $response, 1);

        $this->assertSame($response, $resultado);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Veiculos\Presentation\Http\Router;

use App\Core\Presentation\Http\PresenterInterface;
use App\Veiculos\Application\UseCase\EditarVeiculo\EditarVeiculoUseCase;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Presentation\Http\Controller\EditarVeiculoController;
use App\Veiculos\Presentation\Http\DTO\EditarVeiculoMapper;
use App\Veiculos\Presentation\Http\Router\EditarVeiculoRouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class EditarVeiculoRouterTest extends TestCase {
    public function testInvokeDelegaParaOControllerComIdERetornaAResposta(): void {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode(['marca' => 'Honda']));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($stream);

        $response = $this->createMock(ResponseInterface::class);

        $veiculoEditado = new Veiculo(id: 5, placa: new Placa('ABC1234'), marca: 'Honda', modelo: 'Civic');

        $useCase = $this->createMock(EditarVeiculoUseCase::class);
        $useCase->expects($this->once())
            ->method('executar')
            ->with(5, $this->anything())
            ->willReturn($veiculoEditado);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('success')->willReturn($response);

        $controller = new EditarVeiculoController($useCase, new EditarVeiculoMapper(), $presenter);
        $router = new EditarVeiculoRouter($controller);

        $resultado = $router(5, $request, $response);

        $this->assertSame($response, $resultado);
    }
}

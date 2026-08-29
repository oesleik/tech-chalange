<?php

declare(strict_types=1);

namespace Tests\Veiculos\Presentation\Http\Router;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\Veiculos\Application\UseCase\CriarVeiculo\CriarVeiculoUseCase;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Presentation\Http\Controller\CriarVeiculoController;
use App\Veiculos\Presentation\Http\DTO\CriarVeiculoMapper;
use App\Veiculos\Presentation\Http\Router\CriarVeiculoRouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class CriarVeiculoRouterTest extends TestCase {
    public function testInvokeDelegaParaOControllerERetornaAResposta(): void {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode([
            'placa' => 'ABC1234',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
        ]));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($stream);

        $response = $this->createMock(ResponseInterface::class);

        $veiculoCriado = new Veiculo(id: 1, placa: new Placa('ABC1234'), marca: 'Toyota', modelo: 'Corolla');

        $useCase = $this->createMock(CriarVeiculoUseCase::class);
        $useCase->expects($this->once())->method('executar')->willReturn($veiculoCriado);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('success')
            ->with($response, $this->anything(), HttpStatusCodeEnum::Created)
            ->willReturn($response);

        $controller = new CriarVeiculoController($useCase, new CriarVeiculoMapper(), $presenter);
        $router = new CriarVeiculoRouter($controller);

        $resultado = $router($request, $response);

        $this->assertSame($response, $resultado);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Veiculos\Presentation\Http\Router;

use App\Core\Presentation\Http\PresenterInterface;
use App\Veiculos\Application\UseCase\ObterVeiculo\ObterVeiculoUseCase;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Presentation\Http\Controller\ObterVeiculoController;
use App\Veiculos\Presentation\Http\Router\ObterVeiculoRouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ObterVeiculoRouterTest extends TestCase {
    public function testInvokeDelegaParaOControllerComIdERetornaAResposta(): void {
        $response = $this->createMock(ResponseInterface::class);

        $veiculo = new Veiculo(id: 7, placa: new Placa('ABC1234'), marca: 'Toyota', modelo: 'Corolla');

        $useCase = $this->createMock(ObterVeiculoUseCase::class);
        $useCase->expects($this->once())->method('executar')->with(7)->willReturn($veiculo);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('success')->willReturn($response);

        $controller = new ObterVeiculoController($useCase, $presenter);
        $router = new ObterVeiculoRouter($controller);

        $resultado = $router(7, $response);

        $this->assertSame($response, $resultado);
    }
}

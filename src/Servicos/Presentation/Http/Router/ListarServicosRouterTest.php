<?php

declare(strict_types=1);

namespace Tests\Servicos\Presentation\Http\Router;

use App\Core\Presentation\Http\PresenterInterface;
use App\Servicos\Application\UseCase\ListarServicos\ListarServicosUseCase;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use App\Servicos\Presentation\Http\Controller\ListarServicosController;
use App\Servicos\Presentation\Http\DTO\ListarServicosResponseDTO;
use App\Servicos\Presentation\Http\Router\ListarServicosRouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListarServicosRouterTest extends TestCase {
    public function testInvokeDelegaParaOControllerERetornaAResposta(): void {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(ListarServicosUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->willReturn([Servico::reconstituir(123, 'Revisão', new ValorUnitario(150.0))]);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('success')
            ->with($response, $this->isInstanceOf(ListarServicosResponseDTO::class))
            ->willReturn($response);

        $router = new ListarServicosRouter(new ListarServicosController($useCase, $presenter));

        $this->assertSame($response, $router($request, $response));
    }
}

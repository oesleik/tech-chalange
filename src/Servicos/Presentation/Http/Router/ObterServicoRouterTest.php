<?php

declare(strict_types=1);

namespace Tests\Servicos\Presentation\Http\Router;

use App\Core\Presentation\Http\PresenterInterface;
use App\Servicos\Application\UseCase\ObterServico\ObterServicoUseCase;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use App\Servicos\Presentation\Http\Controller\ObterServicoController;
use App\Servicos\Presentation\Http\DTO\ServicoResponseDTO;
use App\Servicos\Presentation\Http\Router\ObterServicoRouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ObterServicoRouterTest extends TestCase {
    public function testInvokeDelegaParaOControllerComIdERetornaAResposta(): void {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(ObterServicoUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->with(123)
            ->willReturn(Servico::reconstituir(123, 'Revisão', new ValorUnitario(150.0)));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('success')
            ->with($response, $this->isInstanceOf(ServicoResponseDTO::class))
            ->willReturn($response);

        $router = new ObterServicoRouter(new ObterServicoController($useCase, $presenter));

        $this->assertSame($response, $router($request, $response, 123));
    }
}

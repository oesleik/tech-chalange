<?php

declare(strict_types=1);

namespace Tests\Servicos\Presentation\Http\Router;

use App\Core\Presentation\Http\PresenterInterface;
use App\Servicos\Application\UseCase\EditarServico\EditarServicoUseCase;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use App\Servicos\Presentation\Http\Controller\EditarServicoController;
use App\Servicos\Presentation\Http\DTO\ServicoResponseDTO;
use App\Servicos\Presentation\Http\Router\EditarServicoRouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class EditarServicoRouterTest extends TestCase {
    public function testInvokeDelegaParaOControllerComIdERetornaAResposta(): void {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn((string) json_encode([
            'descricao' => 'Diagnóstico',
        ]));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($stream);

        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(EditarServicoUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->with(123)
            ->willReturn(Servico::reconstituir(123, 'Diagnóstico', new ValorUnitario(80.0)));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('success')
            ->with($response, $this->isInstanceOf(ServicoResponseDTO::class))
            ->willReturn($response);

        $router = new EditarServicoRouter(new EditarServicoController($useCase, $presenter));

        $this->assertSame($response, $router($request, $response, 123));
    }
}

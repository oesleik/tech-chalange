<?php

declare(strict_types=1);

namespace Tests\Servicos\Presentation\Http\Router;

use App\Core\Presentation\Http\PresenterInterface;
use App\Servicos\Application\UseCase\CriarServico\CriarServicoUseCase;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use App\Servicos\Presentation\Http\Controller\CriarServicoController;
use App\Servicos\Presentation\Http\DTO\ServicoResponseDTO;
use App\Servicos\Presentation\Http\Router\CriarServicoRouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class CriarServicoRouterTest extends TestCase {
    public function testInvokeDelegaParaOControllerERetornaAResposta(): void {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn((string) json_encode([
            'descricao' => 'Revisão',
            'valor_unitario' => 145,
        ]));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($stream);

        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(CriarServicoUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->willReturn(Servico::reconstituir(123, 'Revisão', new ValorUnitario(145.0)));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('success')
            ->with($response, $this->isInstanceOf(ServicoResponseDTO::class))
            ->willReturn($response);

        $router = new CriarServicoRouter(new CriarServicoController($useCase, $presenter));

        $this->assertSame($response, $router($request, $response));
    }
}

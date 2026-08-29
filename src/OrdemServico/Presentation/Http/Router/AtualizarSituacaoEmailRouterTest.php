<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\Router;

use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\AtualizarSituacao\AtualizarSituacaoUseCaseInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoController;
use App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoEmailController;
use App\OrdemServico\Presentation\Http\Router\AtualizarSituacaoEmailRouter;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AtualizarSituacaoEmailRouterTest extends TestCase {
    private function controller(OrdemServico $os): AtualizarSituacaoEmailController {
        $useCase = $this->createMock(AtualizarSituacaoUseCaseInterface::class);
        $useCase->method('executar')->willReturn($os);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->method('success')->willReturnArgument(0);

        return new AtualizarSituacaoEmailController(new AtualizarSituacaoController($useCase, $presenter));
    }

    public function testAtualizarParaAprovadaDelegaParaOController(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturn(['id_ordem_servico' => 1]);

        $os = new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::APROVADA, 0, new DateTime());
        $router = new AtualizarSituacaoEmailRouter($this->controller($os));

        $resultado = $router->atualizarParaAprovada($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testAtualizarParaRejeitadaDelegaParaOController(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->willReturn(['id_ordem_servico' => 1]);

        $os = new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::REJEITADA, 0, new DateTime());
        $router = new AtualizarSituacaoEmailRouter($this->controller($os));

        $resultado = $router->atualizarParaRejeitada($request, $response);

        $this->assertSame($response, $resultado);
    }
}

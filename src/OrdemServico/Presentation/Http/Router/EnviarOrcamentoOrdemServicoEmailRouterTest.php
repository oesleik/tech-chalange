<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\Router;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\EnviarOrcamento\EnviarOrcamentoUseCaseInterface;
use App\OrdemServico\Presentation\Http\Controller\EnviarOrcamentoOrdemServicoEmailController;
use App\OrdemServico\Presentation\Http\Router\EnviarOrcamentoOrdemServicoEmailRouter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class EnviarOrcamentoOrdemServicoEmailRouterTest extends TestCase {
    public function testInvokeDelegaParaOController(): void {
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(EnviarOrcamentoUseCaseInterface::class);
        $useCase->expects($this->once())->method('executar')->with(1);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('success')
            ->with($response, $this->anything(), HttpStatusCodeEnum::Ok)
            ->willReturn($response);

        $controller = new EnviarOrcamentoOrdemServicoEmailController($useCase, $presenter);
        $router = new EnviarOrcamentoOrdemServicoEmailRouter($controller);

        $resultado = $router(1, $response);

        $this->assertSame($response, $resultado);
    }
}

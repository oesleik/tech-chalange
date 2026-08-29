<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\AtualizarSituacao\AtualizarSituacaoInputDTO;
use App\OrdemServico\Application\UseCase\AtualizarSituacao\AtualizarSituacaoUseCaseInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoController;
use App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoEmailController;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AtualizarSituacaoEmailControllerTest extends TestCase {
    public function testAtualizarParaAprovadaComClaimsValidas(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('jwt_claims', [])->willReturn(['id_ordem_servico' => 1]);

        $os = new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::APROVADA, 0, new DateTime());

        $useCase = $this->createMock(AtualizarSituacaoUseCaseInterface::class);
        $useCase->expects($this->once())
            ->method('executar')
            ->with($this->callback(
                fn(AtualizarSituacaoInputDTO $i) => $i->idOrdemServico === 1
                    && $i->novaSituacao === SituacaoOrdemServicoEnum::APROVADA
            ))
            ->willReturn($os);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('success')
            ->with($response, $this->anything(), HttpStatusCodeEnum::Ok)
            ->willReturn($response);

        $controller = new AtualizarSituacaoEmailController(new AtualizarSituacaoController($useCase, $presenter));

        $resultado = $controller->atualizarParaAprovada($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testAtualizarParaRejeitadaComClaimsValidas(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('jwt_claims', [])->willReturn(['id_ordem_servico' => '7']);

        $os = new OrdemServico(7, 10, 20, SituacaoOrdemServicoEnum::REJEITADA, 0, new DateTime());

        $useCase = $this->createMock(AtualizarSituacaoUseCaseInterface::class);
        $useCase->expects($this->once())
            ->method('executar')
            ->with($this->callback(
                fn(AtualizarSituacaoInputDTO $i) => $i->idOrdemServico === 7
                    && $i->novaSituacao === SituacaoOrdemServicoEnum::REJEITADA
            ))
            ->willReturn($os);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->method('success')->willReturn($response);

        $controller = new AtualizarSituacaoEmailController(new AtualizarSituacaoController($useCase, $presenter));

        $resultado = $controller->atualizarParaRejeitada($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testRetorna422QuandoIdOrdemServicoAusenteNasClaims(): void {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('withStatus')
            ->with(422, 'Ordem de serviço não identificada')
            ->willReturn($response);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('jwt_claims', [])->willReturn([]);

        $useCase = $this->createMock(AtualizarSituacaoUseCaseInterface::class);
        $useCase->expects($this->never())->method('executar');

        $presenter = $this->createMock(PresenterInterface::class);

        $controller = new AtualizarSituacaoEmailController(new AtualizarSituacaoController($useCase, $presenter));

        $resultado = $controller->atualizarParaAprovada($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testRetorna422QuandoIdOrdemServicoNaoNumerico(): void {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('withStatus')
            ->with(422, 'Ordem de serviço não identificada')
            ->willReturn($response);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getAttribute')->with('jwt_claims', [])->willReturn(['id_ordem_servico' => 'abc']);

        $useCase = $this->createMock(AtualizarSituacaoUseCaseInterface::class);
        $useCase->expects($this->never())->method('executar');

        $presenter = $this->createMock(PresenterInterface::class);

        $controller = new AtualizarSituacaoEmailController(new AtualizarSituacaoController($useCase, $presenter));

        $resultado = $controller->atualizarParaRejeitada($request, $response);

        $this->assertSame($response, $resultado);
    }
}

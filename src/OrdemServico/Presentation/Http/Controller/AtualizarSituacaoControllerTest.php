<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\AtualizarSituacao\AtualizarSituacaoInputDTO;
use App\OrdemServico\Application\UseCase\AtualizarSituacao\AtualizarSituacaoUseCaseInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use App\OrdemServico\Domain\Exception\SituacaoBloqueadaException;
use App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoController;
use App\OrdemServico\Presentation\Http\DTO\OrdemServicoResumidaResponseDTO;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class AtualizarSituacaoControllerTest extends TestCase {
    public function testAlterarSituacaoComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);
        $os = new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::EM_DIAGNOSTICO, 0, new DateTime());

        $useCase = $this->createMock(AtualizarSituacaoUseCaseInterface::class);
        $useCase->expects($this->once())
            ->method('executar')
            ->with($this->callback(fn(AtualizarSituacaoInputDTO $i) => $i->idOrdemServico === 1 && $i->novaSituacao === SituacaoOrdemServicoEnum::EM_DIAGNOSTICO))
            ->willReturn($os);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('success')
            ->with($response, $this->isInstanceOf(OrdemServicoResumidaResponseDTO::class), HttpStatusCodeEnum::Ok)
            ->willReturn($response);

        $controller = new AtualizarSituacaoController($useCase, $presenter);
        $resultado = $controller->atualizarParaEmDiagnostico(1, $response);

        $this->assertSame($response, $resultado);
    }

    public function testRetornaNotFoundQuandoOrdemNaoEncontrada(): void {
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(AtualizarSituacaoUseCaseInterface::class);
        $useCase->method('executar')->willThrowException(OrdemServicoNaoEncontradaException::comId(1));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('error')->with($response, $this->isType('string'), HttpStatusCodeEnum::NotFound)->willReturn($response);

        $controller = new AtualizarSituacaoController($useCase, $presenter);
        $controller->atualizarParaFinalizada(1, $response);
    }

    public function testRetornaConflitoQuandoSituacaoBloqueada(): void {
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(AtualizarSituacaoUseCaseInterface::class);
        $useCase->method('executar')->willThrowException(new SituacaoBloqueadaException('bloqueada'));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('error')->with($response, 'bloqueada', HttpStatusCodeEnum::Conflict)->willReturn($response);

        $controller = new AtualizarSituacaoController($useCase, $presenter);
        $controller->atualizarParaEntregue(1, $response);
    }
}

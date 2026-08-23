<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoOutputDTO;
use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoUseCaseInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use App\OrdemServico\Presentation\Http\Controller\ObterOrdemServicoController;
use App\OrdemServico\Presentation\Http\DTO\OrdemServicoCompletaResponseDTO;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ObterOrdemServicoControllerTest extends TestCase {
    public function testExecuteComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);
        $output = new ObterOrdemServicoOutputDTO(
            ordemServico: new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::RECEBIDA, 0, new DateTime()),
            pecas: [],
            servicos: [],
        );

        $useCase = $this->createMock(ObterOrdemServicoUseCaseInterface::class);
        $useCase->expects($this->once())->method('executar')->with(1)->willReturn($output);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('success')
            ->with($response, $this->isInstanceOf(OrdemServicoCompletaResponseDTO::class), HttpStatusCodeEnum::Ok)
            ->willReturn($response);

        $resultado = new ObterOrdemServicoController($useCase, $presenter)->execute(1, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoNaoEncontrada(): void {
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(ObterOrdemServicoUseCaseInterface::class);
        $useCase->method('executar')->willThrowException(OrdemServicoNaoEncontradaException::comId(1));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('error')
            ->with($response, $this->isType('string'), HttpStatusCodeEnum::NotFound)
            ->willReturn($response);

        $resultado = new ObterOrdemServicoController($useCase, $presenter)->execute(1, $response);

        $this->assertSame($response, $resultado);
    }
}

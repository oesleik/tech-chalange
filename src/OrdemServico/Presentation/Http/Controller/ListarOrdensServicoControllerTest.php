<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\ListarOrdensServico\ListarOrdensServicoInputDTO;
use App\OrdemServico\Application\UseCase\ListarOrdensServico\ListarOrdensServicoUseCaseInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Presentation\Http\Controller\ListarOrdensServicoController;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListarOrdensServicoControllerTest extends TestCase {
    public function testExecuteRetornaListaDeOrdens(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);

        $ordens = [new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::RECEBIDA, 0, new DateTime())];

        $useCase = $this->createMock(ListarOrdensServicoUseCaseInterface::class);
        $useCase->expects($this->once())
            ->method('executar')
            ->with($this->isInstanceOf(ListarOrdensServicoInputDTO::class))
            ->willReturn($ordens);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('success')->with($response, $this->isType('object'), HttpStatusCodeEnum::Ok)->willReturn($response);

        $resultado = new ListarOrdensServicoController($useCase, $presenter)->execute($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteRetornaErroParaFiltroInvalido(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['situacao' => 'Invalida']);

        $useCase = $this->createMock(ListarOrdensServicoUseCaseInterface::class);
        $useCase->expects($this->never())->method('executar');

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('error')->with($response, $this->isType('string'), HttpStatusCodeEnum::BadRequest)->willReturn($response);

        $resultado = new ListarOrdensServicoController($useCase, $presenter)->execute($request, $response);

        $this->assertSame($response, $resultado);
    }
}

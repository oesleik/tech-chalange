<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo\GerarRelatorioMediaTempoUseCaseInterface;
use App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo\ServicoRelatorioDTO;
use App\OrdemServico\Presentation\Http\Controller\RelatoriosOrdemServicoController;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class RelatoriosOrdemServicoControllerTest extends TestCase {
    public function testRetornaRelatorioComServicos(): void {
        $response = $this->createMock(ResponseInterface::class);
        $servicos = [new ServicoRelatorioDTO(1, 'Troca de óleo', 49.90, 1.5, 3, 4.5, 1.1, 2.2)];

        $useCase = $this->createMock(GerarRelatorioMediaTempoUseCaseInterface::class);
        $useCase->expects($this->once())->method('executar')->willReturn($servicos);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('success')->with($response, $this->isType('object'), HttpStatusCodeEnum::Ok)->willReturn($response);

        $resultado = new RelatoriosOrdemServicoController($useCase, $presenter)->relatorioMediaTempoServicos($response);

        $this->assertSame($response, $resultado);
    }
}

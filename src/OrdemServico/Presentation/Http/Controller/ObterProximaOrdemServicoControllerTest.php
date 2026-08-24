<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\Controller;

use App\Core\Config\AppConfig;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoOutputDTO;
use App\OrdemServico\Application\UseCase\ObterProximaOrdemServico\ObterProximaOrdemServicoUseCaseInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Presentation\Http\Controller\ObterProximaOrdemServicoController;
use DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ObterProximaOrdemServicoControllerTest extends TestCase {
    public function testExecuteRetorna204QuandoNaoHaProxima(): void {
        $useCase = $this->createMock(ObterProximaOrdemServicoUseCaseInterface::class);
        $useCase->method('executar')->willReturn(null);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('withStatus')->with(204)->willReturn($response);

        $presenter = $this->createMock(PresenterInterface::class);
        $appConfig = $this->createMock(AppConfig::class);

        $resultado = new ObterProximaOrdemServicoController($useCase, $presenter, $appConfig)->execute($response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteRetornaDadosQuandoHaProxima(): void {
        $output = new ObterOrdemServicoOutputDTO(
            ordemServico: new OrdemServico(1, 10, 20, SituacaoOrdemServicoEnum::APROVADA, 0, new DateTime()),
            pecas: [],
            servicos: [],
        );

        $useCase = $this->createMock(ObterProximaOrdemServicoUseCaseInterface::class);
        $useCase->method('executar')->willReturn($output);

        $response = $this->createMock(ResponseInterface::class);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('success')->with($response, $this->isType('object'), HttpStatusCodeEnum::Ok)->willReturn($response);

        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->method('getBaseUrl')->willReturn('http://localhost/');

        $resultado = new ObterProximaOrdemServicoController($useCase, $presenter, $appConfig)->execute($response);

        $this->assertSame($response, $resultado);
    }
}

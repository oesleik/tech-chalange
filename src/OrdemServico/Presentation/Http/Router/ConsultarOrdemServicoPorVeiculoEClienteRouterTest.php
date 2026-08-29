<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\Router;

use App\Clientes\Application\UseCase\ListarClientes\ListarClientesUseCaseInterface;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Presentation\Http\Controller\ConsultarOrdemServicoPorVeiculoEClienteController;
use App\OrdemServico\Presentation\Http\Router\ConsultarOrdemServicoPorVeiculoEClienteRouter;
use App\Veiculos\Application\UseCase\ObterVeiculoPorPlaca\ObterVeiculoPorPlacaUseCase;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ConsultarOrdemServicoPorVeiculoEClienteRouterTest extends TestCase {
    public function testInvokeDelegaParaOController(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('error')
            ->with($response, 'Os campos cpf_cnpj e placa são obrigatórios.', HttpStatusCodeEnum::BadRequest)
            ->willReturn($response);

        $controller = new ConsultarOrdemServicoPorVeiculoEClienteController(
            $this->createMock(ListarClientesUseCaseInterface::class),
            $this->createMock(ObterVeiculoPorPlacaUseCase::class),
            $this->createMock(OrdemServicoGatewayInterface::class),
            $this->createMock(ItensOrdemServicoGatewayInterface::class),
            $presenter,
        );

        $router = new ConsultarOrdemServicoPorVeiculoEClienteRouter($controller);

        $resultado = $router($request, $response);

        $this->assertSame($response, $resultado);
    }
}

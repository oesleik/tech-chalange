<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculos\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoInputDTO;
use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoOutputDTO;
use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoUseCase;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Presentation\Http\Controller\ListarVeiculoController;
use App\Veiculos\Presentation\Http\DTO\ListagemVeiculosResponseDTO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListarVeiculoControllerTest extends TestCase {
    private function criarRequestComQuery(array $query): ServerRequestInterface {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($query);

        return $request;
    }

    public function testExecuteComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComQuery([
            'marca' => 'Toyota',
            'pagina' => '2',
            'porPagina' => '10',
        ]);

        $veiculos = [
            new Veiculo(id: 1, placa: new Placa('ABC1234'), marca: 'Toyota', modelo: 'Corolla'),
        ];

        $output = new ListarVeiculoOutputDTO(
            veiculos: $veiculos,
            total: 1,
            pagina: 2,
            porPagina: 10,
        );

        $useCase = $this->createMock(ListarVeiculoUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->with($this->callback(
                fn(ListarVeiculoInputDTO $input) => $input->marca === 'Toyota'
                    && $input->pagina === 2
                    && $input->porPagina === 10
            ))
            ->willReturn($output);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('success')
            ->with(
                $response,
                $this->isInstanceOf(ListagemVeiculosResponseDTO::class),
            )
            ->willReturn($response);

        $controller = new ListarVeiculoController($useCase, $presenter);

        $resultado = $controller->execute($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoPlacaComFormatoInvalido(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComQuery(['placa' => '123']);

        $useCase = $this->createMock(ListarVeiculoUseCase::class);
        $useCase->expects($this->never())->method('executar');

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('error')
            ->with(
                $response,
                'Placa inválida.',
                HttpStatusCodeEnum::BadRequest,
            )
            ->willReturn($response);

        $controller = new ListarVeiculoController($useCase, $presenter);

        $resultado = $controller->execute($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoUseCaseLancaEntradaInvalida(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComQuery(['marca' => 'Toyota']);

        $useCase = $this->createMock(ListarVeiculoUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->willThrowException(
                new InvalidArgumentException('Parâmetros inválidos')
            );

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('error')
            ->with(
                $response,
                'Parâmetros inválidos',
                HttpStatusCodeEnum::BadRequest,
            )
            ->willReturn($response);

        $controller = new ListarVeiculoController($useCase, $presenter);

        $resultado = $controller->execute($request, $response);

        $this->assertSame($response, $resultado);
    }
}

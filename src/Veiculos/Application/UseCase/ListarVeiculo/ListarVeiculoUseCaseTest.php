<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculos\Application\UseCase\ListarVeiculo;

use App\Veiculos\Application\Gateway\FiltroListagemVeiculo;
use App\Veiculos\Application\Gateway\VeiculoGatewayInterface;
use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoInputDTO;
use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoUseCase;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use PHPUnit\Framework\TestCase;

final class ListarVeiculoUseCaseTest extends TestCase {
    public function testExecutarRetornaVeiculosComPaginacao(): void {
        $input = new ListarVeiculoInputDTO(
            placa: null,
            marca: 'Toyota',
            modelo: 'Corolla',
            pagina: 2,
            porPagina: 10,
        );

        $filtroEsperado = new FiltroListagemVeiculo(
            placa: null,
            marca: 'Toyota',
            modelo: 'Corolla',
            pagina: 2,
            porPagina: 10,
        );

        $veiculos = [
            new Veiculo(id: 1, placa: new Placa('ABC1234'), marca: 'Toyota', modelo: 'Corolla'),
            new Veiculo(id: 2, placa: new Placa('XYZ9876'), marca: 'Toyota', modelo: 'Corolla'),
        ];

        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway
            ->expects($this->once())
            ->method('contar')
            ->with($this->equalTo($filtroEsperado))
            ->willReturn(25);

        $gateway
            ->expects($this->once())
            ->method('listar')
            ->with($this->equalTo($filtroEsperado))
            ->willReturn($veiculos);

        $useCase = new ListarVeiculoUseCase($gateway);

        $resultado = $useCase->executar($input);

        $this->assertSame($veiculos, $resultado->veiculos);
        $this->assertSame(25, $resultado->total);
        $this->assertSame(2, $resultado->pagina);
        $this->assertSame(10, $resultado->porPagina);
    }

    public function testExecutarRetornaListaVaziaQuandoNaoHaResultados(): void {
        $input = new ListarVeiculoInputDTO();

        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway
            ->expects($this->once())
            ->method('contar')
            ->willReturn(0);

        $gateway
            ->expects($this->once())
            ->method('listar')
            ->willReturn([]);

        $useCase = new ListarVeiculoUseCase($gateway);

        $resultado = $useCase->executar($input);

        $this->assertSame([], $resultado->veiculos);
        $this->assertSame(0, $resultado->total);
        $this->assertSame(1, $resultado->pagina);
        $this->assertSame(20, $resultado->porPagina);
    }
}

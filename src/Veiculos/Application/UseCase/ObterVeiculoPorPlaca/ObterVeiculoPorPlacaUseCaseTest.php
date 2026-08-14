<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculos\Application\UseCase\ObterVeiculoPorPlaca;

use App\Veiculos\Application\Gateway\VeiculoGatewayInterface;
use App\Veiculos\Application\UseCase\ObterVeiculoPorPlaca\ObterVeiculoPorPlacaUseCase;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
use PHPUnit\Framework\TestCase;

final class ObterVeiculoPorPlacaUseCaseTest extends TestCase {
    public function testExecutarRetornaVeiculoQuandoEncontrado(): void {
        $veiculo = new Veiculo(
            id: 1,
            placa: new Placa('ABC1234'),
            marca: 'Toyota',
            modelo: 'Corolla',
        );

        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway
            ->expects($this->once())
            ->method('buscarPorPlaca')
            ->with($this->equalTo(new Placa('ABC1234')))
            ->willReturn($veiculo);

        $useCase = new ObterVeiculoPorPlacaUseCase($gateway);

        $resultado = $useCase->executar('ABC1234');

        $this->assertSame($veiculo, $resultado);
    }

    public function testExecutarLancaExcecaoQuandoNaoEncontrado(): void {
        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway
            ->expects($this->once())
            ->method('buscarPorPlaca')
            ->with($this->equalTo(new Placa('XYZ9876')))
            ->willReturn(null);

        $useCase = new ObterVeiculoPorPlacaUseCase($gateway);

        $this->expectException(VeiculoNaoEncontradoException::class);
        $this->expectExceptionMessage('Veículo com placa XYZ9876 não encontrado.');

        $useCase->executar('XYZ9876');
    }
}

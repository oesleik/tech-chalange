<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculos\Application\UseCase\ObterVeiculo;

use App\Veiculos\Application\Gateway\VeiculoGatewayInterface;
use App\Veiculos\Application\UseCase\ObterVeiculo\ObterVeiculoUseCase;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
use PHPUnit\Framework\TestCase;

final class ObterVeiculoUseCaseTest extends TestCase {
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
            ->method('buscarPorId')
            ->with(1)
            ->willReturn($veiculo);

        $useCase = new ObterVeiculoUseCase($gateway);

        $resultado = $useCase->executar(1);

        $this->assertSame($veiculo, $resultado);
    }

    public function testExecutarLancaExcecaoQuandoNaoEncontrado(): void {
        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway
            ->expects($this->once())
            ->method('buscarPorId')
            ->with(99)
            ->willReturn(null);

        $useCase = new ObterVeiculoUseCase($gateway);

        $this->expectException(VeiculoNaoEncontradoException::class);
        $this->expectExceptionMessage('Veículo com id 99 não encontrado.');

        $useCase->executar(99);
    }
}

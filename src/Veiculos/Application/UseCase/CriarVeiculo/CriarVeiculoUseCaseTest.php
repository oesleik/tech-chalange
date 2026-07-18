<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculos\Application\UseCase\CriarVeiculo;

use App\Veiculos\Application\Gateway\VeiculoGatewayInterface;
use App\Veiculos\Application\UseCase\CriarVeiculo\CriarVeiculoUseCase;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoJaCadastradoException;
use PHPUnit\Framework\TestCase;

final class CriarVeiculoUseCaseTest extends TestCase {
    public function testExecutarInsereVeiculoQuandoPlacaDisponivel(): void {
        $veiculo = new Veiculo(
            id: 0,
            placa: new Placa('ABC1234'),
            marca: 'Toyota',
            modelo: 'Corolla',
        );

        $veiculoInserido = $veiculo->comId(10);

        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway
            ->expects($this->once())
            ->method('buscarPorPlaca')
            ->with($veiculo->placa())
            ->willReturn(null);

        $gateway
            ->expects($this->once())
            ->method('inserir')
            ->with($veiculo)
            ->willReturn($veiculoInserido);

        $useCase = new CriarVeiculoUseCase($gateway);

        $resultado = $useCase->executar($veiculo);

        $this->assertSame($veiculoInserido, $resultado);
        $this->assertSame(10, $resultado->id());
    }

    public function testExecutarLancaExcecaoQuandoPlacaJaCadastrada(): void {
        $veiculo = new Veiculo(
            id: 0,
            placa: new Placa('ABC1234'),
            marca: 'Toyota',
            modelo: 'Corolla',
        );

        $veiculoExistente = $veiculo->comId(5);

        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway
            ->expects($this->once())
            ->method('buscarPorPlaca')
            ->with($veiculo->placa())
            ->willReturn($veiculoExistente);

        $gateway
            ->expects($this->never())
            ->method('inserir');

        $useCase = new CriarVeiculoUseCase($gateway);

        $this->expectException(VeiculoJaCadastradoException::class);
        $this->expectExceptionMessage('Veículo com placa ABC-1234 já cadastrado.');

        $useCase->executar($veiculo);
    }
}

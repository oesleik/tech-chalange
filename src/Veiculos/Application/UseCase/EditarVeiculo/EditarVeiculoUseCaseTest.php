<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculos\Application\UseCase\EditarVeiculo;

use App\Veiculos\Application\Gateway\VeiculoGatewayInterface;
use App\Veiculos\Application\UseCase\EditarVeiculo\EditarVeiculoInputDTO;
use App\Veiculos\Application\UseCase\EditarVeiculo\EditarVeiculoUseCase;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoJaCadastradoException;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
use PHPUnit\Framework\TestCase;

final class EditarVeiculoUseCaseTest extends TestCase {
    private function criarVeiculoExistente(): Veiculo {
        return new Veiculo(
            id: 5,
            placa: new Placa('ABC1234'),
            marca: 'Toyota',
            modelo: 'Corolla',
        );
    }

    public function testExecutarLancaExcecaoQuandoVeiculoNaoEncontrado(): void {
        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway
            ->expects($this->once())
            ->method('buscarPorId')
            ->with(5)
            ->willReturn(null);

        $gateway->expects($this->never())->method('atualizar');

        $useCase = new EditarVeiculoUseCase($gateway);

        $this->expectException(VeiculoNaoEncontradoException::class);
        $this->expectExceptionMessage('Veículo com id 5 não encontrado.');

        $useCase->executar(5, new EditarVeiculoInputDTO());
    }

    public function testExecutarAtualizaMarcaEModeloSemAlterarPlaca(): void {
        $veiculoExistente = $this->criarVeiculoExistente();

        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway
            ->expects($this->once())
            ->method('buscarPorId')
            ->with(5)
            ->willReturn($veiculoExistente);

        $gateway
            ->expects($this->never())
            ->method('buscarPorPlaca');

        $gateway
            ->expects($this->once())
            ->method('atualizar')
            ->with($this->callback(
                fn(Veiculo $veiculo) => $veiculo->marca() === 'Honda'
                    && $veiculo->modelo() === 'Civic'
                    && $veiculo->placa()->getValue() === 'ABC1234'
            ))
            ->willReturnArgument(0);

        $useCase = new EditarVeiculoUseCase($gateway);

        $input = new EditarVeiculoInputDTO(
            placa: null,
            marca: 'Honda',
            modelo: 'Civic',
        );

        $resultado = $useCase->executar(5, $input);

        $this->assertSame('Honda', $resultado->marca());
        $this->assertSame('Civic', $resultado->modelo());
        $this->assertSame('ABC1234', $resultado->placa()->getValue());
    }

    public function testExecutarAtualizaPlacaQuandoNovaPlacaDisponivel(): void {
        $veiculoExistente = $this->criarVeiculoExistente();

        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway
            ->expects($this->once())
            ->method('buscarPorId')
            ->with(5)
            ->willReturn($veiculoExistente);

        $gateway
            ->expects($this->once())
            ->method('buscarPorPlaca')
            ->with($this->callback(fn(Placa $placa) => $placa->getValue() === 'XYZ9876'))
            ->willReturn(null);

        $gateway
            ->expects($this->once())
            ->method('atualizar')
            ->with($this->callback(
                fn(Veiculo $veiculo) => $veiculo->placa()->getValue() === 'XYZ9876'
            ))
            ->willReturnArgument(0);

        $useCase = new EditarVeiculoUseCase($gateway);

        $input = new EditarVeiculoInputDTO(placa: 'XYZ9876');

        $resultado = $useCase->executar(5, $input);

        $this->assertSame('XYZ9876', $resultado->placa()->getValue());
    }

    public function testExecutarLancaExcecaoQuandoNovaPlacaJaCadastradaParaOutroVeiculo(): void {
        $veiculoExistente = $this->criarVeiculoExistente();
        $outroVeiculo = new Veiculo(
            id: 9,
            placa: new Placa('XYZ9876'),
            marca: 'Honda',
            modelo: 'Civic',
        );

        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway
            ->expects($this->once())
            ->method('buscarPorId')
            ->with(5)
            ->willReturn($veiculoExistente);

        $gateway
            ->expects($this->once())
            ->method('buscarPorPlaca')
            ->with($this->callback(fn(Placa $placa) => $placa->getValue() === 'XYZ9876'))
            ->willReturn($outroVeiculo);

        $gateway
            ->expects($this->never())
            ->method('atualizar');

        $useCase = new EditarVeiculoUseCase($gateway);

        $this->expectException(VeiculoJaCadastradoException::class);
        $this->expectExceptionMessage('Veículo com placa XYZ-9876 já cadastrado.');

        $useCase->executar(5, new EditarVeiculoInputDTO(placa: 'XYZ9876'));
    }

    public function testExecutarNaoConsultaGatewayQuandoPlacaInformadaEIgualAAtual(): void {
        $veiculoExistente = $this->criarVeiculoExistente();

        $gateway = $this->createMock(VeiculoGatewayInterface::class);
        $gateway
            ->expects($this->once())
            ->method('buscarPorId')
            ->with(5)
            ->willReturn($veiculoExistente);

        $gateway
            ->expects($this->never())
            ->method('buscarPorPlaca');

        $gateway
            ->expects($this->once())
            ->method('atualizar')
            ->with($this->callback(
                fn(Veiculo $veiculo) => $veiculo->placa()->getValue() === 'ABC1234'
            ))
            ->willReturnArgument(0);

        $useCase = new EditarVeiculoUseCase($gateway);

        $input = new EditarVeiculoInputDTO(placa: 'ABC-1234');

        $resultado = $useCase->executar(5, $input);

        $this->assertSame('ABC1234', $resultado->placa()->getValue());
    }
}

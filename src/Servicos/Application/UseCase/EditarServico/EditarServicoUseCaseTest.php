<?php

declare(strict_types=1);

namespace Tests\Servicos\Application\UseCase;

use App\Servicos\Application\Gateway\ServicoGatewayInterface;
use App\Servicos\Application\UseCase\EditarServico\EditarServicoInputDTO;
use App\Servicos\Application\UseCase\EditarServico\EditarServicoUseCase;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\Exception\ServicoNaoEncontradoException;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EditarServicoUseCaseTest extends TestCase {
    public function testAtualizaApenasCamposInformados(): void {
        $servicoExistente = Servico::reconstituir(1, 'Troca de óleo', new ValorUnitario(49.90));

        $gateway = $this->createMock(ServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->with(1)->willReturn($servicoExistente);
        $gateway->method('atualizar')->willReturnArgument(0);

        $resultado = new EditarServicoUseCase($gateway)->executar(
            1,
            new EditarServicoInputDTO(descricao: 'Troca de óleo premium', valorUnitario: null),
        );

        $this->assertSame('Troca de óleo premium', $resultado->descricao());
        // valor unitário não foi enviado, deve permanecer o mesmo
        $this->assertSame(49.90, $resultado->valorUnitario()->getValue());
    }

    public function testAtualizaTodosCamposQuandoInformados(): void {
        $servicoExistente = Servico::reconstituir(1, 'Troca de óleo', new ValorUnitario(49.90));

        $gateway = $this->createMock(ServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->with(1)->willReturn($servicoExistente);
        $gateway->method('atualizar')->willReturnArgument(0);

        $resultado = new EditarServicoUseCase($gateway)->executar(
            1,
            new EditarServicoInputDTO(descricao: 'Alinhamento', valorUnitario: 120.0),
        );

        $this->assertSame('Alinhamento', $resultado->descricao());
        $this->assertSame(120.0, $resultado->valorUnitario()->getValue());
    }

    public function testNaoAlteraNadaQuandoInputVazio(): void {
        $servicoExistente = Servico::reconstituir(1, 'Troca de óleo', new ValorUnitario(49.90));

        $gateway = $this->createMock(ServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->with(1)->willReturn($servicoExistente);
        $gateway->method('atualizar')->willReturnArgument(0);

        $resultado = new EditarServicoUseCase($gateway)->executar(
            1,
            new EditarServicoInputDTO(descricao: null, valorUnitario: null),
        );

        $this->assertSame('Troca de óleo', $resultado->descricao());
        $this->assertSame(49.90, $resultado->valorUnitario()->getValue());
    }

    public function testLancaExcecaoQuandoServicoNaoEncontrado(): void {
        $gateway = $this->createMock(ServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->with(99)->willReturn(null);
        $gateway->expects($this->never())->method('atualizar');

        $this->expectException(ServicoNaoEncontradoException::class);
        new EditarServicoUseCase($gateway)->executar(
            99,
            new EditarServicoInputDTO(descricao: 'Qualquer coisa', valorUnitario: null),
        );
    }

    public function testLancaExcecaoQuandoNovoValorForNegativo(): void {
        $servicoExistente = Servico::reconstituir(1, 'Troca de óleo', new ValorUnitario(49.90));

        $gateway = $this->createMock(ServicoGatewayInterface::class);
        $gateway->method('buscarPorId')->with(1)->willReturn($servicoExistente);
        $gateway->expects($this->never())->method('atualizar');

        $this->expectException(InvalidArgumentException::class);
        new EditarServicoUseCase($gateway)->executar(
            1,
            new EditarServicoInputDTO(descricao: null, valorUnitario: -5.0),
        );
    }
}

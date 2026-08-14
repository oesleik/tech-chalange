<?php

declare(strict_types=1);

namespace Tests\Peca\Application\UseCase;

use App\Peca\Application\Gateway\PecaGatewayInterface;
use App\Peca\Application\UseCase\EditarPeca\EditarPecaInputDTO;
use App\Peca\Application\UseCase\EditarPeca\EditarPecaUseCase;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\Exception\PecaNaoEncontradaException;
use App\Peca\Domain\ValueObject\ValorUnitario;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EditarPecaUseCaseTest extends TestCase {
    public function testAtualizaApenasCamposInformados(): void {
        $pecaExistente = Peca::reconstituir(1, 'Filtro de óleo', new ValorUnitario(49.90));

        $gateway = $this->createMock(PecaGatewayInterface::class);
        $gateway->method('buscarPorId')->with(1)->willReturn($pecaExistente);
        $gateway->method('atualizar')->willReturnArgument(0);

        $resultado = new EditarPecaUseCase($gateway)->executar(
            1,
            new EditarPecaInputDTO(descricao: 'Filtro de óleo premium', valorUnitario: null),
        );

        $this->assertSame('Filtro de óleo premium', $resultado->descricao());
        // valor unitário não foi enviado, deve permanecer o mesmo
        $this->assertSame(49.90, $resultado->valorUnitario()->getValue());
    }

    public function testAtualizaTodosCamposQuandoInformados(): void {
        $pecaExistente = Peca::reconstituir(1, 'Filtro de óleo', new ValorUnitario(49.90));

        $gateway = $this->createMock(PecaGatewayInterface::class);
        $gateway->method('buscarPorId')->with(1)->willReturn($pecaExistente);
        $gateway->method('atualizar')->willReturnArgument(0);

        $resultado = new EditarPecaUseCase($gateway)->executar(
            1,
            new EditarPecaInputDTO(descricao: 'Correia dentada', valorUnitario: 120.0),
        );

        $this->assertSame('Correia dentada', $resultado->descricao());
        $this->assertSame(120.0, $resultado->valorUnitario()->getValue());
    }

    public function testNaoAlteraNadaQuandoInputVazio(): void {
        $pecaExistente = Peca::reconstituir(1, 'Filtro de óleo', new ValorUnitario(49.90));

        $gateway = $this->createMock(PecaGatewayInterface::class);
        $gateway->method('buscarPorId')->with(1)->willReturn($pecaExistente);
        $gateway->method('atualizar')->willReturnArgument(0);

        $resultado = new EditarPecaUseCase($gateway)->executar(
            1,
            new EditarPecaInputDTO(descricao: null, valorUnitario: null),
        );

        $this->assertSame('Filtro de óleo', $resultado->descricao());
        $this->assertSame(49.90, $resultado->valorUnitario()->getValue());
    }

    public function testLancaExcecaoQuandoPecaNaoEncontrada(): void {
        $gateway = $this->createMock(PecaGatewayInterface::class);
        $gateway->method('buscarPorId')->with(99)->willReturn(null);
        $gateway->expects($this->never())->method('atualizar');

        $this->expectException(PecaNaoEncontradaException::class);
        new EditarPecaUseCase($gateway)->executar(
            99,
            new EditarPecaInputDTO(descricao: 'Qualquer coisa', valorUnitario: null),
        );
    }

    public function testLancaExcecaoQuandoNovoValorForNegativo(): void {
        $pecaExistente = Peca::reconstituir(1, 'Filtro de óleo', new ValorUnitario(49.90));

        $gateway = $this->createMock(PecaGatewayInterface::class);
        $gateway->method('buscarPorId')->with(1)->willReturn($pecaExistente);
        $gateway->expects($this->never())->method('atualizar');

        $this->expectException(InvalidArgumentException::class);
        new EditarPecaUseCase($gateway)->executar(
            1,
            new EditarPecaInputDTO(descricao: null, valorUnitario: -5.0),
        );
    }
}

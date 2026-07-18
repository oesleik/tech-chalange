<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculos\Domain\Entity;

use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class VeiculoTest extends TestCase {
    private function criarVeiculo(): Veiculo {
        return new Veiculo(
            id: 1,
            placa: new Placa('ABC1234'),
            marca: 'Toyota',
            modelo: 'Corolla',
        );
    }

    public function testConstrutorEGetters(): void {
        $veiculo = $this->criarVeiculo();

        $this->assertSame(1, $veiculo->id());
        $this->assertSame('ABC1234', $veiculo->placa()->getValue());
        $this->assertSame('Toyota', $veiculo->marca());
        $this->assertSame('Corolla', $veiculo->modelo());
    }

    public function testConstrutorLancaExcecaoQuandoMarcaVazia(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Marca não pode ser vazia.');

        new Veiculo(
            id: 1,
            placa: new Placa('ABC1234'),
            marca: '   ',
            modelo: 'Corolla',
        );
    }

    public function testConstrutorLancaExcecaoQuandoModeloVazio(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Modelo não pode ser vazio.');

        new Veiculo(
            id: 1,
            placa: new Placa('ABC1234'),
            marca: 'Toyota',
            modelo: '',
        );
    }

    public function testComIdRetornaNovaInstanciaImutavel(): void {
        $veiculo = $this->criarVeiculo();

        $comNovoId = $veiculo->comId(99);

        $this->assertNotSame($veiculo, $comNovoId);
        $this->assertSame(99, $comNovoId->id());
        $this->assertSame(1, $veiculo->id());
    }

    public function testComPlacaRetornaNovaInstanciaImutavel(): void {
        $veiculo = $this->criarVeiculo();
        $novaPlaca = new Placa('XYZ9876');

        $comNovaPlaca = $veiculo->comPlaca($novaPlaca);

        $this->assertNotSame($veiculo, $comNovaPlaca);
        $this->assertSame('XYZ9876', $comNovaPlaca->placa()->getValue());
        $this->assertSame('ABC1234', $veiculo->placa()->getValue());
    }

    public function testComMarcaRetornaNovaInstanciaImutavel(): void {
        $veiculo = $this->criarVeiculo();

        $comNovaMarca = $veiculo->comMarca('Honda');

        $this->assertNotSame($veiculo, $comNovaMarca);
        $this->assertSame('Honda', $comNovaMarca->marca());
        $this->assertSame('Toyota', $veiculo->marca());
    }

    public function testComModeloRetornaNovaInstanciaImutavel(): void {
        $veiculo = $this->criarVeiculo();

        $comNovoModelo = $veiculo->comModelo('Civic');

        $this->assertNotSame($veiculo, $comNovoModelo);
        $this->assertSame('Civic', $comNovoModelo->modelo());
        $this->assertSame('Corolla', $veiculo->modelo());
    }
}

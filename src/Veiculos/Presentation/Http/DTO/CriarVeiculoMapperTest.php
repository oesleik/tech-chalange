<?php

declare(strict_types=1);

namespace Tests\Veiculos\Presentation\Http\DTO;

use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Presentation\Http\DTO\CriarVeiculoMapper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CriarVeiculoMapperTest extends TestCase {
    public function testParseiaDadosValidosParaVeiculo(): void {
        $veiculo = CriarVeiculoMapper::parse([
            'placa' => ' abc1d23 ',
            'marca' => ' Toyota ',
            'modelo' => ' Corolla ',
        ]);

        $this->assertInstanceOf(Veiculo::class, $veiculo);
        $this->assertSame('ABC1D23', $veiculo->placa()->getValue());
        $this->assertSame('Toyota', $veiculo->marca());
        $this->assertSame('Corolla', $veiculo->modelo());
    }

    public function testLancaExcecaoQuandoPlacaAusente(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Placa é obrigatória');

        CriarVeiculoMapper::parse(['marca' => 'Toyota', 'modelo' => 'Corolla']);
    }

    public function testLancaExcecaoQuandoMarcaAusente(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Marca é obrigatória');

        CriarVeiculoMapper::parse(['placa' => 'ABC1D23', 'modelo' => 'Corolla']);
    }

    public function testLancaExcecaoQuandoModeloAusente(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Modelo é obrigatório');

        CriarVeiculoMapper::parse(['placa' => 'ABC1D23', 'marca' => 'Toyota']);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculos\Domain\Entity;

use App\Veiculos\Domain\Entity\Placa;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PlacaTest extends TestCase {
    public function testAceitaPlacaNoFormatoAntigo(): void {
        $placa = new Placa('ABC1234');

        $this->assertSame('ABC1234', $placa->getValue());
    }

    public function testAceitaPlacaNoFormatoMercosul(): void {
        $placa = new Placa('ABC1D23');

        $this->assertSame('ABC1D23', $placa->getValue());
    }

    public function testNormalizaPlacaComHifenEEspacos(): void {
        $placa = new Placa(' abc-1234 ');

        $this->assertSame('ABC1234', $placa->getValue());
    }

    public function testGetFormattedValueRetornaComHifen(): void {
        $placa = new Placa('ABC1234');

        $this->assertSame('ABC-1234', $placa->getFormattedValue());
    }

    public function testToStringRetornaValorFormatado(): void {
        $placa = new Placa('ABC1234');

        $this->assertSame('ABC-1234', (string) $placa);
    }

    public function testLancaExcecaoQuandoPlacaComTamanhoInvalido(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Placa inválida.');

        new Placa('ABC123');
    }

    public function testLancaExcecaoQuandoPlacaComFormatoInvalido(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Placa inválida.');

        new Placa('1234ABC');
    }

    public function testLancaExcecaoQuandoPlacaVazia(): void {
        $this->expectException(InvalidArgumentException::class);

        new Placa('');
    }
}

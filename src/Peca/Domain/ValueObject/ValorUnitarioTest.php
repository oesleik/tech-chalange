<?php

declare(strict_types=1);

namespace Tests\Peca\Domain\ValueObject;

use App\Peca\Domain\ValueObject\ValorUnitario;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ValorUnitarioTest extends TestCase {
    public function testAceitaValorPositivo(): void {
        $vo = new ValorUnitario(49.90);
        $this->assertSame(49.90, $vo->getValue());
    }

    public function testAceitaValorZero(): void {
        $vo = new ValorUnitario(0.0);
        $this->assertSame(0.0, $vo->getValue());
    }

    public function testLancaExcecaoQuandoNegativo(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor unitário não pode ser negativo.');
        new ValorUnitario(-0.01);
    }

    public function testFormataValorComVirgulaEMilhar(): void {
        $vo = new ValorUnitario(1234.5);
        $this->assertSame('1.234,50', $vo->getFormattedValue());
    }

    public function testFormataValorSemCasasDecimaisInformadas(): void {
        $vo = new ValorUnitario(10.0);
        $this->assertSame('10,00', $vo->getFormattedValue());
    }
}

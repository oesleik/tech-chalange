<?php

declare(strict_types=1);

namespace Tests\Clientes\Domain\ValueObject;

use App\Clientes\Domain\ValueObject\Cnpj;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CnpjTest extends TestCase {
    public function testAceitaCnpjValidoFormatado(): void {
        $cnpj = new Cnpj('11.222.333/0001-81');

        $this->assertSame('11222333000181', $cnpj->getValue());
        $this->assertSame('11.222.333/0001-81', $cnpj->getFormattedValue());
    }

    public function testRejeitaCnpjInvalido(): void {
        $this->expectException(InvalidArgumentException::class);

        new Cnpj('11222333000180');
    }

    public function testMascaraCnpj(): void {
        $this->assertSame('11.***.***/****-81', (new Cnpj('11222333000181'))->getMaskedValue());
    }
}

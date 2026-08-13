<?php

declare(strict_types=1);

namespace Tests\Clientes\Domain\ValueObject;

use App\Clientes\Domain\ValueObject\Cpf;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CpfTest extends TestCase {
    public function testAceitaCpfValidoFormatado(): void {
        $cpf = new Cpf('529.982.247-25');

        $this->assertSame('52998224725', $cpf->getValue());
        $this->assertSame('529.982.247-25', $cpf->getFormattedValue());
    }

    public function testRejeitaCpfInvalido(): void {
        $this->expectException(InvalidArgumentException::class);

        new Cpf('52998224724');
    }

    public function testMascaraCpf(): void {
        $this->assertSame('52*.***.***-25', (new Cpf('52998224725'))->getMaskedValue());
    }
}

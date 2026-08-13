<?php

declare(strict_types=1);

namespace Tests\Clientes\Domain\ValueObject;

use App\Clientes\Domain\ValueObject\Telefone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TelefoneTest extends TestCase {
    public function testAceitaTelefoneValido(): void {
        $telefone = new Telefone('5412345678');

        $this->assertSame('5412345678', $telefone->getValue());
        $this->assertSame('********78', $telefone->getMaskedValue());
    }

    public function testRejeitaTelefoneComCaracterInvalido(): void {
        $this->expectException(InvalidArgumentException::class);

        new Telefone('abc1234567');
    }

    public function testRejeitaTelefoneCurto(): void {
        $this->expectException(InvalidArgumentException::class);

        new Telefone('1234567');
    }
}

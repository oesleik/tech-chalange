<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TelefoneValueTest extends TestCase {
    public function testEmptyThrowsException(): void {
        $this->expectException(InvalidArgumentException::class);
        new TelefoneValue("");
    }

    public function testIncompletoThrowsException(): void {
        $this->expectException(InvalidArgumentException::class);
        new TelefoneValue("15645");
    }

    public function testInvalidoThrowsException(): void {
        $this->expectException(InvalidArgumentException::class);
        new TelefoneValue("5499999999D");
    }

    public static function provideValidTelefones(): \Generator {
        yield ['54999999999'];
        yield ['(54) 9 9999-9999'];
        yield ['+55 54 99999-9999'];
    }

    #[DataProvider('provideValidTelefones')]
    public function testGetValue(string $value): void {
        $telefone = new TelefoneValue($value);
        $this->assertEquals($value, $telefone->getValue());
        $this->assertEquals($value, $telefone->__toString());
    }

    public function testGetMaskedValue(): void {
        $telefone = new TelefoneValue("54999999999");
        $this->assertEquals("*********99", $telefone->getMaskedValue());

        $telefone = new TelefoneValue("(54) 9 9999-9999");
        $this->assertEquals("**************99", $telefone->getMaskedValue());
    }

}

<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CnpjValueTest extends TestCase {
    public function testEmptyThrowsException(): void {
		$this->expectException(InvalidArgumentException::class);
		new CnpjValue("");
	}

	public function testGetUnformattedValue(): void {
		$cnpj = new CnpjValue("AB.345.678/000A-91");
		$this->assertEquals("AB345678000A91", $cnpj->getValue());
		$this->assertEquals("AB345678000A91", $cnpj->__toString());
	}

	public function testGetMaskedValue(): void {
		$cnpj = new CnpjValue("AB345678000A91");
		$this->assertEquals("AB.***.***/****-91", $cnpj->getMaskedValue());
	}

	public function testGetFormattedValue(): void {
		$cnpj = new CnpjValue("AB345678000A91");
		$this->assertEquals("AB.345.678/000A-91", $cnpj->getFormattedValue());
	}

}

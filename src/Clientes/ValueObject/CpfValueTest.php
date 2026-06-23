<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CpfValueTest extends TestCase {
    public function testEmptyThrowsException(): void {
		$this->expectException(InvalidArgumentException::class);
		new CpfValue("");
	}

	public function testGetUnformattedValue(): void {
		$cpf = new CpfValue("529.982.247-25");
		$this->assertEquals("52998224725", $cpf->getValue());
		$this->assertEquals("52998224725", $cpf->__toString());
	}

	public function testGetMaskedValue(): void {
		$cpf = new CpfValue("52998224725");
		$this->assertEquals("52*.***.***-25", $cpf->getMaskedValue());
	}

	public function testGetFormattedValue(): void {
		$cpf = new CpfValue("52998224725");
		$this->assertEquals("529.982.247-25", $cpf->getFormattedValue());
	}
}

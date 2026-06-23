<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

use CnpjValidatorTest;
use CpfValidatorTest;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

class CpfOrCnpjValueFactoryTest extends TestCase {
	public function testEmptyThrowsException(): void {
		$this->expectException(InvalidArgumentException::class);
		CpfOrCnpjValueFactory::make("");
	}

	public function testInvalidThrowsException(): void {
		$this->expectException(InvalidArgumentException::class);
		CpfOrCnpjValueFactory::make("123456");
	}

	#[DataProviderExternal(CpfValidatorTest::class, "provideValidCpfs")]
	public function testMakeCpf(string $value): void {
		$cpf = CpfOrCnpjValueFactory::make($value);
		$this->assertInstanceOf(CpfValue::class, $cpf);
	}

	#[DataProviderExternal(CnpjValidatorTest::class, "provideValidCnpjs")]
	public function testMakeCnpj(string $value): void {
		$cnpj = CpfOrCnpjValueFactory::make($value);
		$this->assertInstanceOf(CnpjValue::class, $cnpj);
	}

}

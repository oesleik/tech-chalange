<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

abstract class CpfOrCnpjFactory {
	public static function make(string $value): Cpf|Cnpj {
		$cleanedValue = str_replace(['-', '.'], '', $value);

		if (strlen($cleanedValue) <= 11) {
			return new Cpf($value);
		}

		return new Cnpj($value);
	}
}

<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

use InvalidArgumentException;

abstract class CpfOrCnpjValueFactory {
    public static function make(string $value): CpfValue|CnpjValue {
		try {
			return new CpfValue($value);
		} catch (InvalidArgumentException) {
		}

		try {
			return new CnpjValue($value);
		} catch (InvalidArgumentException) {
		}

		throw new InvalidArgumentException("CPF/CNPJ inválido");
    }
}

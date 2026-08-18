<?php

declare(strict_types=1);

namespace App\Clientes\Domain\ValueObject;

use InvalidArgumentException;

final class CpfOrCnpjValueFactory {
    public static function make(string $value): Cpf|Cnpj {
        try {
            return new Cpf($value);
        } catch (InvalidArgumentException) {
        }

        try {
            return new Cnpj($value);
        } catch (InvalidArgumentException) {
        }

        throw new InvalidArgumentException('CPF/CNPJ inválido');
    }
}

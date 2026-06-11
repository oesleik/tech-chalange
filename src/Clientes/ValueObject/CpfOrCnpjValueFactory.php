<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

abstract class CpfOrCnpjValueFactory {
    public static function make(string $value): CpfValue|CnpjValue {
        $cleanedValue = str_replace(['-', '.'], '', $value);

        if (strlen($cleanedValue) <= 11) {
            return new CpfValue($value);
        }

        return new CnpjValue($value);
    }
}

<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

use InvalidArgumentException;

class TelefoneValue {
    public function __construct(
        private string $telefone
    ) {
		if (strlen($telefone) < 8 || preg_match("/[^\d\(\)\s\+-]/", $telefone)) {
			throw new InvalidArgumentException("Telefone inválido");
		}
	}

    public function getValue(): string {
        return $this->telefone;
    }

    public function getMaskedValue(): string {
        $masked = preg_replace('/./', '*', $this->telefone);
        $masked[-1] = $this->telefone[-1];
        $masked[-2] = $this->telefone[-2];
        return $masked;
    }

    public function __toString() {
        return $this->getValue();
    }
}

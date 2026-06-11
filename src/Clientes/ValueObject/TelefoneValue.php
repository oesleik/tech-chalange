<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

class TelefoneValue {
    public function __construct(
        private string $telefone
    ) {}

    public function getValue(): string {
        return $this->telefone;
    }

    public function getMaskedValue(): string {
        if (strlen($this->telefone) < 8) {
            return preg_replace('/./', '*', $this->telefone);
        }

        $masked = preg_replace('/./', '*', $this->telefone);
        $masked[-1] = $this->telefone[-1];
        $masked[-2] = $this->telefone[-2];
        return $masked;
    }

    public function __toString() {
        return $this->getValue();
    }
}

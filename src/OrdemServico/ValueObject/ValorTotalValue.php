<?php

declare(strict_types=1);

namespace App\OrdemServico\ValueObject;

class ValorTotalValue {
    public function __construct(
        private float $valor
    ) {
        if ($valor < 0) {
            throw new \InvalidArgumentException("Valor total não pode ser negativo");
        }
    }

    public function getValue(): float {
        return $this->valor;
    }

    public function __toString(): string {
        return (string) $this->valor;
    }
}

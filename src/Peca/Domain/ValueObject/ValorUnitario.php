<?php

declare(strict_types=1);

namespace App\Peca\Domain\ValueObject;

use InvalidArgumentException;

final class ValorUnitario {
    private float $valor;

    public function __construct(float $valor) {
        if ($valor < 0) {
            throw new InvalidArgumentException('Valor unitário não pode ser negativo.');
        }
        $this->valor = $valor;
    }

    public function getValue(): float {
        return $this->valor;
    }

    public function getFormattedValue(): string {
        return number_format($this->valor, 2, ',', '.');
    }
}
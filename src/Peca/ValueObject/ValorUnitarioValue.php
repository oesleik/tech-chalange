<?php
declare(strict_types=1);
namespace App\Peca\ValueObject;

class ValorUnitarioValue {
    public function __construct(
        private float $valorUnitario
    ) {}

    public function getValue(): float {
        return $this->valorUnitario;
    }

    public function getFormattedValue(): string {
        return number_format($this->valorUnitario, 2, ',', '.');
    }

    public function __toString(): string {
        return $this->getFormattedValue();
    }
}
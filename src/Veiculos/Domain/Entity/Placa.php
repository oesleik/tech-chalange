<?php

declare(strict_types=1);

namespace App\Veiculos\Domain\Entity;

use InvalidArgumentException;

class Placa {
    public function __construct(
        private string $placa
    ) {
        $this->placa = $this->normalizar($this->placa);

        if (!$this->ehPlacaValida($this->placa)) {
            throw new InvalidArgumentException('Placa inválida.');
        }
    }

    public function getValue(): string {
        return $this->placa;
    }

    public function getFormattedValue(): string {
        return substr($this->placa, 0, 3) . '-' . substr($this->placa, 3);
    }

    public function __toString(): string {
        return $this->getFormattedValue();
    }

    private function normalizar(string $placa): string {
        return strtoupper(
            str_replace(['-', ' '], '', trim($placa))
        );
    }

    private function ehPlacaValida(string $placa): bool {
        if (strlen($placa) !== 7) {
            return false;
        }

        return preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $placa) === 1;
    }
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Domain\Entity;

final class ServicoOrdemServico {
    public function __construct(
        private int $idServico,
        private int $quantidade,
        private float $valorUnitario,
    ) {}

    public function idServico(): int {
        return $this->idServico;
    }

    public function quantidade(): int {
        return $this->quantidade;
    }

    public function valorUnitario(): float {
        return $this->valorUnitario;
    }

    public function subtotal(): float {
        return round($this->quantidade * $this->valorUnitario, 2);
    }
}

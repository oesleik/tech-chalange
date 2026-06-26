<?php

declare(strict_types=1);

namespace App\OrdemServico\Model;

class ServicoOrdemServicoModel {
    public function __construct(
        private int $idServico,
        private int $quantidade,
        private float $valorUnitario,
    ) {}

    public function getIdServico(): int {
        return $this->idServico;
    }

    public function getQuantidade(): int {
        return $this->quantidade;
    }

    public function getValorUnitario(): float {
        return $this->valorUnitario;
    }

    public function getSubtotal(): float {
        return round($this->quantidade * $this->valorUnitario, 2);
    }
}

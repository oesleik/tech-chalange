<?php

declare(strict_types=1);

namespace App\OrdemServico\Model;

class PecaOrdemServicoModel {
    public function __construct(
        private int $idPeca,
        private int $quantidade,
        private ?float $valorUnitario = null,
    ) {}

    public function getIdPeca(): int {
        return $this->idPeca;
    }

    public function getQuantidade(): int {
        return $this->quantidade;
    }

    public function getValorUnitario(): ?float {
        return $this->valorUnitario;
    }
}

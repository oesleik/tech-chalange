<?php

declare(strict_types=1);

namespace App\Estoque\Model;

class EstoquePecaModel {
    public function __construct(
        private int $idPeca,
        private int $estoqueAtual,
    ) {}

    public function getIdPeca(): int {
        return $this->idPeca;
    }

    public function getEstoqueAtual(): int {
        return $this->estoqueAtual;
    }

}

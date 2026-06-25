<?php

declare(strict_types=1);

namespace App\Estoque\Model;

class LancamentoEstoqueModel {
    public function __construct(
        private int $id,
        private int $idPeca,
        private int $quantidade,
        private TipoLancamentoEstoqueEnum $tipoLancamento,
    ) {}

    public function getId(): int {
        return $this->id;
    }

    public function getIdPeca(): int {
        return $this->idPeca;
    }

    public function getQuantidade(): int {
        return $this->quantidade;
    }

    public function getTipoLancamento(): TipoLancamentoEstoqueEnum {
        return $this->tipoLancamento;
    }
}

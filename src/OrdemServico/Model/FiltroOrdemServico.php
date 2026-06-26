<?php

declare(strict_types=1);

namespace App\OrdemServico\Model;

use App\OrdemServico\Model\SituacaoOrdemServicoEnum;

readonly class FiltroOrdemServico {
    public function __construct(
        private ?SituacaoOrdemServicoEnum $situacao = null,
        private ?int $idCliente = null,
        private ?int $idVeiculo = null,
        private int $limit = 0,
    ) {}

    public function getSituacao(): ?SituacaoOrdemServicoEnum {
        return $this->situacao;
    }

    public function getIdCliente(): ?int {
        return $this->idCliente;
    }

    public function getIdVeiculo(): ?int {
        return $this->idVeiculo;
    }

    public function getLimit(): int {
        return $this->limit;
    }

}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Domain\Entity;

use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use DateTime;

final class OrdemServico {
    public function __construct(
        private int $id,
        private int $idCliente,
        private int $idVeiculo,
        private SituacaoOrdemServicoEnum $situacao,
        private float $valorTotal,
        private DateTime $dataSolicitacao,
        private ?DateTime $dataAprovacao = null,
    ) {}

    public function id(): int {
        return $this->id;
    }

    public function idCliente(): int {
        return $this->idCliente;
    }

    public function idVeiculo(): int {
        return $this->idVeiculo;
    }

    public function situacao(): SituacaoOrdemServicoEnum {
        return $this->situacao;
    }

    public function valorTotal(): float {
        return $this->valorTotal;
    }

    public function dataSolicitacao(): DateTime {
        return $this->dataSolicitacao;
    }

    public function dataAprovacao(): ?DateTime {
        return $this->dataAprovacao;
    }

    public function comId(int $id): self {
        $clone = clone $this;
        $clone->id = $id;
        return $clone;
    }

    public function comSituacao(SituacaoOrdemServicoEnum $situacao): self {
        $clone = clone $this;
        $clone->situacao = $situacao;
        return $clone;
    }

    public function comValorTotal(float $valorTotal): self {
        $clone = clone $this;
        $clone->valorTotal = $valorTotal;
        return $clone;
    }

    public function comDataAprovacao(?DateTime $dataAprovacao): self {
        $clone = clone $this;
        $clone->dataAprovacao = $dataAprovacao;
        return $clone;
    }
}

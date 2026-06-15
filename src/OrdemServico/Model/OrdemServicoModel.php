<?php

declare(strict_types=1);

namespace App\OrdemServico\Model;

use App\OrdemServico\ValueObject\SituacaoOrdemValue;
use App\OrdemServico\ValueObject\ValorTotalValue;
use DateTime;

class OrdemServicoModel
{
    public function __construct(
        private int $id,
        private int $idCliente,
        private int $idVeiculo,
        private SituacaoOrdemValue $situacao,
        private ?ValorTotalValue $valorTotal,
        private DateTime $dataSolicitacao,
        private ?DateTime $dataAprovacao = null,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdCliente(): int
    {
        return $this->idCliente;
    }

    public function getIdVeiculo(): int
    {
        return $this->idVeiculo;
    }

    public function getSituacao(): SituacaoOrdemValue
    {
        return $this->situacao;
    }

    public function getValorTotal(): ?ValorTotalValue
    {
        return $this->valorTotal;
    }

    public function getDataSolicitacao(): DateTime
    {
        return $this->dataSolicitacao;
    }

    public function getDataAprovacao(): ?DateTime
    {
        return $this->dataAprovacao;
    }

    public function withId(int $id): self
    {
        $new = clone $this;
        $new->id = $id;
        return $new;
    }

    public function withSituacao(SituacaoOrdemValue $situacao): self
    {
        $new = clone $this;
        $new->situacao = $situacao;
        return $new;
    }

    public function withValorTotal(?ValorTotalValue $valorTotal): self
    {
        $new = clone $this;
        $new->valorTotal = $valorTotal;
        return $new;
    }

    public function withDataAprovacao(?DateTime $dataAprovacao): self
    {
        $new = clone $this;
        $new->dataAprovacao = $dataAprovacao;
        return $new;
    }
}

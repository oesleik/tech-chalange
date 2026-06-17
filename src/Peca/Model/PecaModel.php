<?php

declare(strict_types=1);

namespace App\Peca\Model;

use App\Peca\ValueObject\DescricaoValue;
use App\Peca\ValueObject\ValorUnitarioValue;

class PecaModel {
    public function __construct(
        private int $id,
        private DescricaoValue $descricao,
        private ValorUnitarioValue $valorUnitario,
    ) {}

    public function getId(): int {
        return $this->id;
    }

    public function getDescricao(): DescricaoValue {
        return $this->descricao;
    }

    public function getValorUnitario(): ValorUnitarioValue {
        return $this->valorUnitario;
    }

    public function withId(int $id): self {
        $new = clone $this;
        $new->id = $id;
        return $new;
    }

    public function withDescricao(DescricaoValue $descricao): self {
        $new = clone $this;
        $new->descricao = $descricao;
        return $new;
    }

    public function withValorUnitario(ValorUnitarioValue $valorUnitario): self {
        $new = clone $this;
        $new->valorUnitario = $valorUnitario;
        return $new;
    }
}

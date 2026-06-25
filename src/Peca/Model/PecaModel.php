<?php

declare(strict_types=1);

namespace App\Peca\Model;

class PecaModel {
    public function __construct(
        private int $id,
        private string $descricao,
        private float $valorUnitario,
    ) {}

    public function getId(): int {
        return $this->id;
    }

    public function getDescricao(): string {
        return $this->descricao;
    }

    public function getValorUnitario(): float {
        return $this->valorUnitario;
    }

    public function withId(int $id): self {
        $new = clone $this;
        $new->id = $id;
        return $new;
    }

    public function withDescricao(string $descricao): self {
        $new = clone $this;
        $new->descricao = $descricao;
        return $new;
    }

    public function withValorUnitario(float $valorUnitario): self {
        $new = clone $this;
        $new->valorUnitario = $valorUnitario;
        return $new;
    }
}

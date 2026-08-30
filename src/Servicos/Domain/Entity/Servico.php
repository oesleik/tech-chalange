<?php

declare(strict_types=1);

namespace App\Servicos\Domain\Entity;

use App\Servicos\Domain\ValueObject\ValorUnitario;
use InvalidArgumentException;

final class Servico {
    private function __construct(
        private ?int $id,
        private string $descricao,
        private ValorUnitario $valorUnitario,
    ) {
        if (trim($descricao) === '') {
            throw new InvalidArgumentException('Descrição é obrigatória.');
        }
        $this->descricao = trim($descricao);
    }

    public static function criar(string $descricao, ValorUnitario $valorUnitario): self {
        return new self(null, $descricao, $valorUnitario);
    }

    public static function reconstituir(int $id, string $descricao, ValorUnitario $valorUnitario): self {
        return new self($id, $descricao, $valorUnitario);
    }

    public function id(): ?int {
        return $this->id;
    }

    public function descricao(): string {
        return $this->descricao;
    }

    public function valorUnitario(): ValorUnitario {
        return $this->valorUnitario;
    }

    public function comId(int $id): self {
        return new self($id, $this->descricao, $this->valorUnitario);
    }

    public function comDescricao(string $descricao): self {
        return new self($this->id, $descricao, $this->valorUnitario);
    }

    public function comValorUnitario(ValorUnitario $valorUnitario): self {
        return new self($this->id, $this->descricao, $valorUnitario);
    }
}

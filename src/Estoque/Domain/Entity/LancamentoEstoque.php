<?php

declare(strict_types=1);

namespace App\Estoque\Domain\Entity;

use App\Estoque\Domain\Enum\TipoLancamentoEnum;

// representa uma movimentação registrada no estoque (entrada ou baixa)
final class LancamentoEstoque {
    private function __construct(
        private ?int $id,
        private int $pecaId,
        private int $quantidade,
        private TipoLancamentoEnum $tipo,
    ) {}

    public static function criar(int $pecaId, int $quantidade, TipoLancamentoEnum $tipo): self {
        return new self(null, $pecaId, $quantidade, $tipo);
    }

    // usado ao reconstituir do banco — id já existe
    public static function reconstituir(int $id, int $pecaId, int $quantidade, TipoLancamentoEnum $tipo): self {
        return new self($id, $pecaId, $quantidade, $tipo);
    }

    public function id(): ?int {
        return $this->id;
    }
    public function pecaId(): int {
        return $this->pecaId;
    }
    public function quantidade(): int {
        return $this->quantidade;
    }
    public function tipo(): TipoLancamentoEnum {
        return $this->tipo;
    }

    public function comId(int $id): self {
        return new self($id, $this->pecaId, $this->quantidade, $this->tipo);
    }
}

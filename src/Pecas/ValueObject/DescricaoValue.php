<?php
declare(strict_types=1);
namespace App\Peca\ValueObject;

class DescricaoValue {
    public function __construct(
        private string $descricao
    ) {
        $this->descricao = trim($descricao);
    }

    public function getValue(): string {
        return $this->descricao;
    }

    public function __toString(): string {
        return $this->getValue();
    }
}
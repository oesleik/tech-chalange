<?php

declare(strict_types=1);

namespace App\Estoque\Application\UseCase\ConsultarEstoquePorPeca;

// o estoque atual é calculado (não armazenado), então vai como OutputDTO
final class ConsultarEstoquePorPecaOutputDTO {
    public function __construct(
        public readonly int $pecaId,
        public readonly int $estoqueAtual,
    ) {}
}

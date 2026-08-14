<?php

declare(strict_types=1);

namespace App\Peca\Application\UseCase\CriarPeca;

final class CriarPecaInputDTO {
    public function __construct(
        public readonly string $descricao,
        public readonly float $valorUnitario,
    ) {}
}

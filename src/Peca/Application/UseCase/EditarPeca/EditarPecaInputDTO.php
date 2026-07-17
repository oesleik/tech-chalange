<?php

declare(strict_types=1);

namespace App\Peca\Application\UseCase\EditarPeca;

final class EditarPecaInputDTO {
    public function __construct(
        public readonly ?string $descricao = null,
        public readonly ?float $valorUnitario = null,
    ) {}
}
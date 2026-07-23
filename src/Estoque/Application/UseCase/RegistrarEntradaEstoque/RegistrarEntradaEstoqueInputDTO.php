<?php

declare(strict_types=1);

namespace App\Estoque\Application\UseCase\RegistrarEntradaEstoque;

final class RegistrarEntradaEstoqueInputDTO {
    public function __construct(
        public readonly int $pecaId,
        public readonly int $quantidade,
    ) {}
}

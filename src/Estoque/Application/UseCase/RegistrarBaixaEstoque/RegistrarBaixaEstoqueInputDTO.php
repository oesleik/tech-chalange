<?php

declare(strict_types=1);

namespace App\Estoque\Application\UseCase\RegistrarBaixaEstoque;

final class RegistrarBaixaEstoqueInputDTO {
    public function __construct(
        public readonly int $pecaId,
        public readonly int $quantidade,
    ) {}
}

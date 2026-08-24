<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\EditarItensOrdemServico;

final class EditarItensInputDTO {
    /** @param array<array{id: int, quantidade: int}> $itens */
    public function __construct(
        public readonly int $idOrdemServico,
        public readonly array $itens,
        public readonly bool $substituir = false,
    ) {}
}

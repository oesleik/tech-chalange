<?php

declare(strict_types=1);

namespace App\Servicos\Application\UseCase\CriarServico;

final class CriarServicoInputDTO {
    public function __construct(
        public readonly string $descricao,
        public readonly float $valorUnitario,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Servicos\Application\UseCase\EditarServico;

final class EditarServicoInputDTO {
    public function __construct(
        public readonly ?string $descricao = null,
        public readonly ?float $valorUnitario = null,
    ) {}
}

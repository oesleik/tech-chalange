<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\CriarOrdemServico;

final class CriarOrdemServicoInputDTO {
    public function __construct(
        public readonly int $idCliente,
        public readonly int $idVeiculo,
    ) {}
}

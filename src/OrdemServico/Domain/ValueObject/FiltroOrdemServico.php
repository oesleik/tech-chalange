<?php

declare(strict_types=1);

namespace App\OrdemServico\Domain\ValueObject;

use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;

final class FiltroOrdemServico {
    public function __construct(
        public readonly ?SituacaoOrdemServicoEnum $situacao = null,
        public readonly ?int $idCliente = null,
        public readonly ?int $idVeiculo = null,
        public readonly int $limit = 0,
    ) {}
}

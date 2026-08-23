<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\ListarOrdensServico;

use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;

final class ListarOrdensServicoInputDTO {
    public function __construct(
        public readonly ?SituacaoOrdemServicoEnum $situacao = null,
        public readonly ?int $idCliente = null,
        public readonly ?int $idVeiculo = null,
    ) {}
}

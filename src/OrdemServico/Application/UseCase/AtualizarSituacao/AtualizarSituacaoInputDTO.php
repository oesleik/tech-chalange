<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\AtualizarSituacao;

use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;

final class AtualizarSituacaoInputDTO {
    public function __construct(
        public readonly int $idOrdemServico,
        public readonly SituacaoOrdemServicoEnum $novaSituacao,
    ) {}
}

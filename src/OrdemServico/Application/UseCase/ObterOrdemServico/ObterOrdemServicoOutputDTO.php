<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\ObterOrdemServico;

use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use App\OrdemServico\Domain\Entity\ServicoOrdemServico;

final class ObterOrdemServicoOutputDTO {
    /**
     * @param PecaOrdemServico[] $pecas
     * @param ServicoOrdemServico[] $servicos
     */
    public function __construct(
        public readonly OrdemServico $ordemServico,
        public readonly array $pecas,
        public readonly array $servicos,
    ) {}
}

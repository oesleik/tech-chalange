<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo;

interface RelatorioMediaTempoRepositoryInterface {
    /** @return ServicoRelatorioDTO[] */
    public function buscar(): array;
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo;

interface GerarRelatorioMediaTempoUseCaseInterface {
    /** @return ServicoRelatorioDTO[] */
    public function executar(): array;
}

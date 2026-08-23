<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\ObterProximaOrdemServico;

use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoOutputDTO;

interface ObterProximaOrdemServicoUseCaseInterface {
    public function executar(): ?ObterOrdemServicoOutputDTO;
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\CriarOrdemServico;

use App\OrdemServico\Domain\Entity\OrdemServico;

interface CriarOrdemServicoUseCaseInterface {
    public function executar(CriarOrdemServicoInputDTO $input): OrdemServico;
}

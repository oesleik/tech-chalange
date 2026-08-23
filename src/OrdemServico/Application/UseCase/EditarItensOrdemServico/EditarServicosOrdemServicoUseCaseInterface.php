<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\EditarItensOrdemServico;

interface EditarServicosOrdemServicoUseCaseInterface {
    public function executar(EditarItensInputDTO $input): void;
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\EditarItensOrdemServico;

interface EditarPecasOrdemServicoUseCaseInterface {
    public function executar(EditarItensInputDTO $input): void;
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\ObterOrdemServico;

interface ObterOrdemServicoUseCaseInterface {
    public function executar(int $id): ObterOrdemServicoOutputDTO;
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\EnviarOrcamento;

interface EnviarOrcamentoUseCaseInterface {
    public function executar(int $idOrdemServico): void;
}

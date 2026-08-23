<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\AtualizarSituacao;

use App\OrdemServico\Domain\Entity\OrdemServico;

interface AtualizarSituacaoUseCaseInterface {
    public function executar(AtualizarSituacaoInputDTO $input): OrdemServico;
}

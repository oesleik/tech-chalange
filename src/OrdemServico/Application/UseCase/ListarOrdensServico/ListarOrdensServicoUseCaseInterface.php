<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\ListarOrdensServico;

interface ListarOrdensServicoUseCaseInterface {
    /** @return array<int, \App\OrdemServico\Domain\Entity\OrdemServico> */
    public function executar(ListarOrdensServicoInputDTO $input): array;
}

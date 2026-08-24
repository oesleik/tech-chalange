<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo;

final class GerarRelatorioMediaTempoUseCase implements GerarRelatorioMediaTempoUseCaseInterface {
    public function __construct(
        private readonly RelatorioMediaTempoRepositoryInterface $repository,
    ) {}

    public function executar(): array {
        return $this->repository->buscar();
    }
}

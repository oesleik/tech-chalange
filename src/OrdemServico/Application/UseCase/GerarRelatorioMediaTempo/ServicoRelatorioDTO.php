<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo;

final class ServicoRelatorioDTO {
    public function __construct(
        public readonly int $idServico,
        public readonly string $descricao,
        public readonly float $valorUnitario,
        public readonly float $mediaTempo,
        public readonly int $quantidadeExecucoes,
        public readonly float $totalTempoExecutando,
        public readonly float $minTempoExecucao,
        public readonly float $maxTempoExecucao,
    ) {}
}

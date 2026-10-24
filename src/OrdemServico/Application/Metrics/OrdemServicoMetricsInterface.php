<?php

declare(strict_types=1);

namespace App\OrdemServico\Application\Metrics;

use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;

interface OrdemServicoMetricsInterface {
    public function registrarCriacao(): void;

    public function registrarTransicao(
        SituacaoOrdemServicoEnum $situacaoAnterior,
        SituacaoOrdemServicoEnum $novaSituacao,
        float $tempoNaSituacaoAnteriorMs,
    ): void;
}

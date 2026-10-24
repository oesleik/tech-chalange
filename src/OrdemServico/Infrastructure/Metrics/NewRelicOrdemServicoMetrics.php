<?php

declare(strict_types=1);

namespace App\OrdemServico\Infrastructure\Metrics;

use App\Infrastructure\NewRelic\NewRelicAgentInterface;
use App\OrdemServico\Application\Metrics\OrdemServicoMetricsInterface;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;

// Os nomes são contrato com dashboards e alertas. Ver docs/observability/newrelic.md.
final class NewRelicOrdemServicoMetrics implements OrdemServicoMetricsInterface {
    private const PREFIXO = 'Custom/OrdemServico/';

    public function __construct(
        private readonly NewRelicAgentInterface $agent,
    ) {}

    public function registrarCriacao(): void {
        $this->agent->recordMetric(self::PREFIXO . 'Criada', 1);
    }

    public function registrarTransicao(
        SituacaoOrdemServicoEnum $situacaoAnterior,
        SituacaoOrdemServicoEnum $novaSituacao,
        float $tempoNaSituacaoAnteriorMs,
    ): void {
        $this->agent->recordMetric(
            self::PREFIXO . 'TempoNaSituacao/' . $situacaoAnterior->value,
            $tempoNaSituacaoAnteriorMs,
        );
        $this->agent->recordMetric(self::PREFIXO . 'Transicao/' . $novaSituacao->value, 1);
    }
}

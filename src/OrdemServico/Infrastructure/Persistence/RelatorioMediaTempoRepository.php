<?php

declare(strict_types=1);

namespace App\OrdemServico\Infrastructure\Persistence;

use App\Core\AppDatabase;
use App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo\RelatorioMediaTempoRepositoryInterface;
use App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo\ServicoRelatorioDTO;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use PDO;

final class RelatorioMediaTempoRepository implements RelatorioMediaTempoRepositoryInterface {
    public function __construct(
        private readonly AppDatabase $pdo,
    ) {}

    public function buscar(): array {
        $query = "SELECT
            sos.id_servico, s.descricao, s.valor_unitario,
            COUNT(DISTINCT(sos.id)) as quantidade_execucoes,
            SUM(sos.quantidade) as total_tempo_executando,
            MIN(sos.quantidade) as min_tempo_execucao,
            MAX(sos.quantidade) as max_tempo_execucao
        FROM servicos_ordem_servico sos
        JOIN servicos s ON s.id = sos.id_servico
        JOIN ordens_servico os ON os.id = sos.id_ordem_servico
        WHERE os.situacao IN (?, ?)
        GROUP BY sos.id_servico
        ORDER BY ((total_tempo_executando / quantidade_execucoes) * valor_unitario) DESC, s.descricao ASC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            SituacaoOrdemServicoEnum::FINALIZADA->value,
            SituacaoOrdemServicoEnum::ENTREGUE->value,
        ]);

        $servicos = [];
        while ($row = $stmt->fetchObject()) {
            $servicos[] = new ServicoRelatorioDTO(
                idServico: (int) $row->id_servico,
                descricao: $row->descricao,
                valorUnitario: (float) $row->valor_unitario,
                mediaTempo: round($row->total_tempo_executando / $row->quantidade_execucoes, 2),
                quantidadeExecucoes: (int) $row->quantidade_execucoes,
                totalTempoExecutando: (float) $row->total_tempo_executando,
                minTempoExecucao: (float) $row->min_tempo_execucao,
                maxTempoExecucao: (float) $row->max_tempo_execucao,
            );
        }

        return $servicos;
    }
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Service;

use App\Core\AppDatabase;
use App\OrdemServico\Contract\Relatorios\RelatorioMediaTempoServicosResponse;
use App\OrdemServico\Contract\Relatorios\ServicoRelatorioMediaTempoServicosResponse;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use PDO;

class RelatorioMediaTempoServicosService {
    public function __construct(
        private AppDatabase $pdo,
    ) {}

    public function gerarRelatorio(): RelatorioMediaTempoServicosResponse {
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
			ORDER BY ((total_tempo_executando / quantidade_execucoes) * valor_unitario) DESC, s.descricao ASC
		";

        $situacoesFinalizadas = [
            SituacaoOrdemServicoEnum::FINALIZADA->value,
            SituacaoOrdemServicoEnum::ENTREGUE->value,
        ];

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([...$situacoesFinalizadas]);

        /** @var ServicoRelatorioMediaTempoServicosResponse[] */
        $servicos = [];

        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $servicos[] = new ServicoRelatorioMediaTempoServicosResponse(
                id_servico: $row->id_servico,
                descricao: $row->descricao,
                valor_unitario: intval($row->valor_unitario),
                media_tempo: round($row->total_tempo_executando / $row->quantidade_execucoes, 2),
                quantidade_execucoes: intval($row->quantidade_execucoes),
                total_tempo_executando: floatval($row->total_tempo_executando),
                min_tempo_execucao: floatval($row->min_tempo_execucao),
                max_tempo_execucao: floatval($row->max_tempo_execucao),
            );
        }

        return new RelatorioMediaTempoServicosResponse($servicos);
    }

}

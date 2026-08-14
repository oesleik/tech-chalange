<?php

declare(strict_types=1);

namespace App\OrdemServico\Service;

use App\Core\AppDatabase;
use PDO;

class CalculoValorTotalOrdemServicoService {
    public function __construct(
        private AppDatabase $pdo,
        private OrdemServicoService $ordemServicoService
    ) {}

    public function calcularEAtualizar(int $idOrdemServico): float {
        $totalPecas = $this->calcularTotalPecas($idOrdemServico);
        $totalServicos = $this->calcularTotalServicos($idOrdemServico);
        $valorTotal = $totalPecas + $totalServicos;

        $this->ordemServicoService->atualizarValorTotal($idOrdemServico, $valorTotal);

        return $valorTotal;
    }

    private function calcularTotalPecas(int $idOrdemServico): float {
        $stmt = $this->pdo->prepare(
            "SELECT pos.quantidade, p.valor_unitario
             FROM pecas_ordem_servico pos
             JOIN pecas p ON p.id = pos.id_peca
             WHERE pos.id_ordem_servico = ?"
        );
        $stmt->execute([$idOrdemServico]);
        $itens = $stmt->fetchAll(PDO::FETCH_OBJ);

        $total = 0;
        foreach ($itens as $item) {
            $total += $this->makeSubtotal(intval($item->quantidade), floatval($item->valor_unitario));
        }

        return $total;
    }

    private function calcularTotalServicos(int $idOrdemServico): float {
        $stmt = $this->pdo->prepare(
            "SELECT sos.quantidade, s.valor_unitario
             FROM servicos_ordem_servico sos
             JOIN servicos s ON s.id = sos.id_servico
             WHERE sos.id_ordem_servico = ?"
        );
        $stmt->execute([$idOrdemServico]);
        $itens = $stmt->fetchAll(PDO::FETCH_OBJ);

        $total = 0;
        foreach ($itens as $item) {
            $total += $this->makeSubtotal(intval($item->quantidade), floatval($item->valor_unitario));
        }

        return $total;
    }

    private function makeSubtotal(int $quantidade, float $valorUnitario): float {
        return round($quantidade * $valorUnitario, 2);
    }
}

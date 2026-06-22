<?php

declare(strict_types=1);

namespace App\OrdemServico\Service;

use App\Core\AppDatabase;
use App\OrdemServico\ValueObject\ValorTotalValue;
use PDO;

class CalculoValorTotalOrdemServicoService {
    public function __construct(
        private AppDatabase $pdo,
        private OrdemServicoService $ordemServicoService
    ) {}

    public function calcularEAtualizar(int $idOrdemServico): ValorTotalValue {
        $totalPecas = $this->calcularTotalPecas($idOrdemServico);
        $totalServicos = $this->calcularTotalServicos($idOrdemServico);

        $valorTotal = new ValorTotalValue($totalPecas + $totalServicos);

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
            $total += $this->safeMultiplicacaoQuantidadePorValor(intval($item->quantidade), floatval($item->valor_unitario));
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
            $total += $this->safeMultiplicacaoQuantidadePorValor(intval($item->quantidade), floatval($item->valor_unitario));
        }

        return $total;
    }

    private function safeMultiplicacaoQuantidadePorValor(int $quantidade, float $valorUnitario): float {
        $quantidade = intval($quantidade);
        $valor = floatval($valorUnitario);

        if ($quantidade < 0) {
            throw new \InvalidArgumentException("Quantidade não pode ser negativa");
        }

        if ($valor < 0) {
            throw new \InvalidArgumentException("Valor unitário não pode ser negativo");
        }

        return round($quantidade * $valor, 2);
    }
}

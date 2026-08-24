<?php

declare(strict_types=1);

namespace App\OrdemServico\Infrastructure\Persistence;

use App\Core\AppDatabase;
use App\Core\Database\TransactionHandler;
use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use App\OrdemServico\Domain\Entity\ServicoOrdemServico;
use PDO;

final class ItensOrdemServicoGateway implements ItensOrdemServicoGatewayInterface {
    public function __construct(
        private readonly AppDatabase $pdo,
        private readonly TransactionHandler $transactionHandler,
        private readonly OrdemServicoGatewayInterface $ordemServicoGateway,
    ) {}

    public function buscarPecasPorOrdemServico(int $idOrdemServico): array {
        $stmt = $this->pdo->prepare(
            'SELECT pos.id_peca, pos.quantidade, p.valor_unitario
             FROM pecas_ordem_servico pos
             JOIN pecas p ON p.id = pos.id_peca
             WHERE pos.id_ordem_servico = ?'
        );
        $stmt->execute([$idOrdemServico]);

        $pecas = [];
        while ($row = $stmt->fetchObject()) {
            $pecas[] = new PecaOrdemServico(
                idPeca: (int) $row->id_peca,
                quantidade: (int) $row->quantidade,
                valorUnitario: (float) $row->valor_unitario,
            );
        }

        return $pecas;
    }

    public function buscarServicosPorOrdemServico(int $idOrdemServico): array {
        $stmt = $this->pdo->prepare(
            'SELECT sos.id_servico, sos.quantidade, s.valor_unitario
             FROM servicos_ordem_servico sos
             JOIN servicos s ON s.id = sos.id_servico
             WHERE sos.id_ordem_servico = ?'
        );
        $stmt->execute([$idOrdemServico]);

        $servicos = [];
        while ($row = $stmt->fetchObject()) {
            $servicos[] = new ServicoOrdemServico(
                idServico: (int) $row->id_servico,
                quantidade: (int) $row->quantidade,
                valorUnitario: (float) $row->valor_unitario,
            );
        }

        return $servicos;
    }

    public function adicionarPecas(OrdemServico $ordemServico, array $pecas): void {
        $tsx  = $this->transactionHandler->beginTransaction($this->pdo);
        $stmt = $this->pdo->prepare(
            'INSERT INTO pecas_ordem_servico (id_ordem_servico, id_peca, quantidade) VALUES (?, ?, ?)'
        );

        foreach ($pecas as $peca) {
            $stmt->execute([$ordemServico->id(), $peca->idPeca(), $peca->quantidade()]);
        }

        $this->recalcularValorTotal($ordemServico->id());
        $tsx->commit();
    }

    public function substituirPecas(OrdemServico $ordemServico, array $pecas): void {
        $tsx = $this->transactionHandler->beginTransaction($this->pdo);
        $this->pdo->prepare('DELETE FROM pecas_ordem_servico WHERE id_ordem_servico = ?')
            ->execute([$ordemServico->id()]);
        $this->adicionarPecas($ordemServico, $pecas);
        $tsx->commit();
    }

    public function adicionarServicos(OrdemServico $ordemServico, array $servicos): void {
        $tsx  = $this->transactionHandler->beginTransaction($this->pdo);
        $stmt = $this->pdo->prepare(
            'INSERT INTO servicos_ordem_servico (id_ordem_servico, id_servico, quantidade) VALUES (?, ?, ?)'
        );

        foreach ($servicos as $servico) {
            $stmt->execute([$ordemServico->id(), $servico->idServico(), $servico->quantidade()]);
        }

        $this->recalcularValorTotal($ordemServico->id());
        $tsx->commit();
    }

    public function substituirServicos(OrdemServico $ordemServico, array $servicos): void {
        $tsx = $this->transactionHandler->beginTransaction($this->pdo);
        $this->pdo->prepare('DELETE FROM servicos_ordem_servico WHERE id_ordem_servico = ?')
            ->execute([$ordemServico->id()]);
        $this->adicionarServicos($ordemServico, $servicos);
        $tsx->commit();
    }

    private function recalcularValorTotal(int $idOrdemServico): void {
        $totalPecas = $this->somarItens(
            'SELECT pos.quantidade, p.valor_unitario
             FROM pecas_ordem_servico pos JOIN pecas p ON p.id = pos.id_peca
             WHERE pos.id_ordem_servico = ?',
            $idOrdemServico,
        );

        $totalServicos = $this->somarItens(
            'SELECT sos.quantidade, s.valor_unitario
             FROM servicos_ordem_servico sos JOIN servicos s ON s.id = sos.id_servico
             WHERE sos.id_ordem_servico = ?',
            $idOrdemServico,
        );

        $this->ordemServicoGateway->atualizarValorTotal($idOrdemServico, $totalPecas + $totalServicos);
    }

    private function somarItens(string $sql, int $idOrdemServico): float {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idOrdemServico]);
        $total = 0.0;
        foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $item) {
            $total += round((int) $item->quantidade * (float) $item->valor_unitario, 2);
        }
        return $total;
    }
}

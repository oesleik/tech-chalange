<?php

declare(strict_types=1);

namespace App\OrdemServico\Service;

use App\OrdemServico\Model\OrdemServicoModel;
use App\Core\AppDatabase;
use App\Core\Database\TransactionHandler;
use App\OrdemServico\Model\PecaOrdemServicoModel;
use App\OrdemServico\Model\ServicoOrdemServicoModel;
use PDO;

class ItensOrdemServicoService {
    public function __construct(
        private AppDatabase $pdo,
        private TransactionHandler $transactionHandler
    ) {}

    /** @return PecaOrdemServicoModel[] */
    public function obterPecasPorIdOrdemServico(int $idOrdemServico): array {
        $stmt = $this->pdo->prepare(
            "SELECT pos.id_peca, pos.quantidade, p.valor_unitario
             FROM pecas_ordem_servico pos
             JOIN pecas p ON p.id = pos.id_peca
             WHERE pos.id_ordem_servico = ?"
        );
        $stmt->execute([$idOrdemServico]);
        $pecas = [];

        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $pecas[] = new PecaOrdemServicoModel(
                idPeca: $row->id_peca,
                quantidade: $row->quantidade,
                valorUnitario: isset($row->valor_unitario) ? floatval($row->valor_unitario) : null,
            );
        }

        return $pecas;
    }

    /** @param PecaOrdemServicoModel[] $pecas */
    public function adicionarPecas(OrdemServicoModel $ordemServico, array $pecas): void {
        $tsx = $this->transactionHandler->beginTransaction($this->pdo);

        $sql = "INSERT INTO pecas_ordem_servico (id_ordem_servico, id_peca, quantidade) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);

        foreach ($pecas as $peca) {
            $stmt->execute([
                $ordemServico->getId(),
                $peca->getIdPeca(),
                $peca->getQuantidade(),
            ]);
        }

        $tsx->commit();
    }

    /** @param PecaOrdemServicoModel[] $pecas */
    public function atualizarPecas(OrdemServicoModel $ordemServico, array $pecas): void {
        $tsx = $this->transactionHandler->beginTransaction($this->pdo);

        $stmt = $this->pdo->prepare("DELETE FROM pecas_ordem_servico WHERE id_ordem_servico = ?");
        $stmt->execute([$ordemServico->getId()]);

        $this->adicionarPecas($ordemServico, $pecas);
        $tsx->commit();
    }

    /** @return ServicoOrdemServicoModel[] */
    public function obterServicosPorIdOrdemServico(int $idOrdemServico): array {
        $stmt = $this->pdo->prepare(
            "SELECT sos.id_servico, sos.quantidade, s.valor_unitario
             FROM servicos_ordem_servico sos
             JOIN servicos s ON s.id = sos.id_servico
             WHERE sos.id_ordem_servico = ?"
        );
        $stmt->execute([$idOrdemServico]);
        $servicos = [];

        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $servicos[] = new ServicoOrdemServicoModel(
                idServico: $row->id_servico,
                quantidade: $row->quantidade,
                valorUnitario: isset($row->valor_unitario) ? floatval($row->valor_unitario) : null,
            );
        }

        return $servicos;
    }

    /** @param ServicoOrdemServicoModel[] $servicos */
    public function adicionarServicos(OrdemServicoModel $ordemServico, array $servicos): void {
        $tsx = $this->transactionHandler->beginTransaction($this->pdo);

        $sql = "INSERT INTO servicos_ordem_servico (id_ordem_servico, id_servico, quantidade) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);

        foreach ($servicos as $servico) {
            $stmt->execute([
                $ordemServico->getId(),
                $servico->getIdServico(),
                $servico->getQuantidade(),
            ]);
        }

        $tsx->commit();
    }

    /** @param ServicoOrdemServicoModel[] $servicos */
    public function atualizarServicos(OrdemServicoModel $ordemServico, array $servicos): void {
        $tsx = $this->transactionHandler->beginTransaction($this->pdo);

        $stmt = $this->pdo->prepare("DELETE FROM servicos_ordem_servico WHERE id_ordem_servico = ?");
        $stmt->execute([$ordemServico->getId()]);

        $this->adicionarServicos($ordemServico, $servicos);
        $tsx->commit();
    }
}

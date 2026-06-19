<?php

declare(strict_types=1);

namespace App\OrdemServico\Service;

use App\OrdemServico\Model\OrdemServicoModel;
use App\Core\AppDatabase;
use App\Core\Database\TransactionHandler;
use App\OrdemServico\Model\PecaOrdemServicoModel;
use App\OrdemServico\Model\ServicoOrdemServicoModel;

class ItensOrdemServicoService {
    public function __construct(
        private AppDatabase $pdo,
        private TransactionHandler $transactionHandler
    ) {}

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

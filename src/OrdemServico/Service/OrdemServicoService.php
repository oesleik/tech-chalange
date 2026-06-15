<?php

declare(strict_types=1);

namespace App\OrdemServico\Service;

use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\ValueObject\SituacaoOrdemValue;
use App\OrdemServico\ValueObject\ValorTotalValue;
use App\Core\AppDatabase;
use DateTime;
use PDO;

class OrdemServicoService
{
    public function __construct(
        private AppDatabase $pdo
    ) {}

    /** @return OrdemServicoModel[] */
    public function listarOrdensServico(): array
    {
        $result = $this->pdo->query(
            "SELECT * FROM ordens_servico ORDER BY data_solicitacao DESC",
            PDO::FETCH_OBJ
        );
        $ordensServico = [];

        foreach ($result as $row) {
            $ordensServico[] = $this->gerarModelPorRow($row);
        }

        return $ordensServico;
    }

    public function obterOrdemServicoPorId(int $id): ?OrdemServicoModel
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ordens_servico WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetchObject();
        return $result ? $this->gerarModelPorRow($result) : null;
    }

    /** @return OrdemServicoModel[] */
    public function listarOrdensServicoPorStatus(string $status): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM ordens_servico WHERE situacao = ? ORDER BY data_solicitacao DESC"
        );
        $stmt->execute([$status]);

        $ordensServico = [];
        while ($row = $stmt->fetchObject()) {
            $ordensServico[] = $this->gerarModelPorRow($row);
        }

        return $ordensServico;
    }

    /** @return OrdemServicoModel[] */
    public function listarOrdensServicoPorCliente(int $idCliente): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM ordens_servico WHERE id_cliente = ? ORDER BY data_solicitacao DESC"
        );
        $stmt->execute([$idCliente]);

        $ordensServico = [];
        while ($row = $stmt->fetchObject()) {
            $ordensServico[] = $this->gerarModelPorRow($row);
        }

        return $ordensServico;
    }

    public function criarOrdemServico(OrdemServicoModel $ordemServico): OrdemServicoModel
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO ordens_servico (id_cliente, id_veiculo, situacao, valor_total, data_solicitacao) 
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $ordemServico->getIdCliente(),
            $ordemServico->getIdVeiculo(),
            $ordemServico->getSituacao()->getValue(),
            $ordemServico->getValorTotal()?->getValue(),
            $ordemServico->getDataSolicitacao()->format('Y-m-d H:i:s'),
        ]);

        $id = intval($this->pdo->lastInsertId());
        return $ordemServico->withId($id);
    }

    public function atualizarSituacao(int $id, SituacaoOrdemValue $situacao): bool
    {
        $dataAprovacao = null;
        if (in_array($situacao->getValue(), ['Aprovada', 'Rejeitada'])) {
            $dataAprovacao = (new DateTime())->format('Y-m-d H:i:s');
        }

        $sql = "UPDATE ordens_servico SET situacao = ?";
        $params = [$situacao->getValue()];

        if ($dataAprovacao) {
            $sql .= ", data_aprovacao = ?";
            $params[] = $dataAprovacao;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function atualizarValorTotal(int $id, ValorTotalValue $valorTotal): bool
    {
        $stmt = $this->pdo->prepare("UPDATE ordens_servico SET valor_total = ? WHERE id = ?");
        return $stmt->execute([$valorTotal->getValue(), $id]);
    }

    public function deletarOrdemServico(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM ordens_servico WHERE id = ?");
        return $stmt->execute([$id]);
    }

    private function gerarModelPorRow(object $row): OrdemServicoModel
    {
        return new OrdemServicoModel(
            id: intval($row->id),
            idCliente: intval($row->id_cliente),
            idVeiculo: intval($row->id_veiculo),
            situacao: new SituacaoOrdemValue($row->situacao),
            valorTotal: $row->valor_total !== null ? new ValorTotalValue(floatval($row->valor_total)) : null,
            dataSolicitacao: new DateTime($row->data_solicitacao),
            dataAprovacao: $row->data_aprovacao !== null ? new DateTime($row->data_aprovacao) : null,
        );
    }
}

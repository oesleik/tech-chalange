<?php

declare(strict_types=1);

namespace App\OrdemServico\Service;

use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\FiltroOrdemServico;
use App\Core\AppDatabase;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use DateTime;
use InvalidArgumentException;
use PDO;

class OrdemServicoService {
    public function __construct(
        private AppDatabase $pdo
    ) {}

    /** @return OrdemServicoModel[] */
    public function listarOrdensServico(): array {
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

    public function obterOrdemServicoPorId(int $id): ?OrdemServicoModel {
        $stmt = $this->pdo->prepare("SELECT * FROM ordens_servico WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetchObject();
        return $result ? $this->gerarModelPorRow($result) : null;
    }

    public function obterOrdemServicoPorClienteEVeiculo(int $idCliente, int $idVeiculo): ?OrdemServicoModel {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM ordens_servico
             WHERE id_cliente = ? AND id_veiculo = ?
             ORDER BY data_solicitacao DESC
             LIMIT 1"
        );
        $stmt->execute([$idCliente, $idVeiculo]);
        $result = $stmt->fetchObject();
        return $result ? $this->gerarModelPorRow($result) : null;
    }

    public function obterProximaOrdemServicoNaFila(): ?OrdemServicoModel {
        $stmt = $this->pdo->prepare("SELECT * FROM ordens_servico WHERE situacao = ? ORDER BY data_aprovacao ASC LIMIT 1");
        $stmt->execute([SituacaoOrdemServicoEnum::APROVADA->value]);
        $result = $stmt->fetchObject();

        if (!$result) {
            $stmt = $this->pdo->prepare("SELECT * FROM ordens_servico WHERE situacao = ? ORDER BY data_solicitacao ASC LIMIT 1");
            $stmt->execute([SituacaoOrdemServicoEnum::RECEBIDA->value]);
            $result = $stmt->fetchObject();
        }

        return $result ? $this->gerarModelPorRow($result) : null;
    }

    /** @return OrdemServicoModel[] */
    public function listarOrdensServicoPorStatus(string $status): array {
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
    public function listarOrdensServicoPorCliente(int $idCliente): array {
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

    /** @return OrdemServicoModel[] */
    public function filtrarOrdensServico(FiltroOrdemServico $filtro): array {
        $sql = "SELECT * FROM ordens_servico WHERE 1=1";
        $params = [];

        if ($filtro->getSituacao() !== null) {
            $sql .= " AND situacao = ?";
            $params[] = $filtro->getSituacao()->value;
        }

        if ($filtro->getIdCliente() !== null) {
            $sql .= " AND id_cliente = ?";
            $params[] = $filtro->getIdCliente();
        }

        if ($filtro->getIdVeiculo() !== null) {
            $sql .= " AND id_veiculo = ?";
            $params[] = $filtro->getIdVeiculo();
        }

        if ($filtro->getIdOrdem() !== null) {
            $sql .= " AND id = ?";
            $params[] = $filtro->getIdOrdem();
        }

        $sql .= " ORDER BY data_solicitacao DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $ordensServico = [];
        while ($row = $stmt->fetchObject()) {
            $ordensServico[] = $this->gerarModelPorRow($row);
        }

        return $ordensServico;
    }

    public function criarOrdemServico(OrdemServicoModel $ordemServico): OrdemServicoModel {
        $stmt = $this->pdo->prepare(
            "INSERT INTO ordens_servico (id_cliente, id_veiculo, situacao, valor_total, data_solicitacao)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $ordemServico->getIdCliente(),
            $ordemServico->getIdVeiculo(),
            $ordemServico->getSituacao()->value,
            $ordemServico->getValorTotal(),
            $ordemServico->getDataSolicitacao()->format('Y-m-d H:i:s'),
        ]);

        $id = intval($this->pdo->lastInsertId());
        return $ordemServico->withId($id);
    }

    public function atualizarSituacao(OrdemServicoModel $ordemServico, SituacaoOrdemServicoEnum $novaSituacao): OrdemServicoModel {
        if (empty($ordemServico->getId())) {
            throw new InvalidArgumentException("Id da ordem de serviço não informada");
        }

        if ($ordemServico->getSituacao() == $novaSituacao) {
            return $ordemServico;
        }

        if (!$ordemServico->getSituacao()->podeAlterarSituacao($novaSituacao)) {
            throw new SituacaoBloqueadaException(sprintf(
                "Não é possível alterar uma ordem de serviço de %s para %s.",
                $ordemServico->getSituacao()->getFormattedValue(),
                $novaSituacao->getFormattedValue()
            ));
        }

        $sql = "UPDATE ordens_servico SET situacao = ?";
        $params = [$novaSituacao->value];

        if ($novaSituacao->deveModificarDataAprovacao()) {
            $sql .= ", data_aprovacao = ?";
            $params[] = new DateTime()->format('Y-m-d H:i:s');
        }

        $sql .= " WHERE id = ?";
        $params[] = $ordemServico->getId();

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $ordemServico->withSituacao($novaSituacao);
    }

    public function atualizarValorTotal(int $id, float $valorTotal): bool {
        $stmt = $this->pdo->prepare("UPDATE ordens_servico SET valor_total = ? WHERE id = ?");
        return $stmt->execute([$valorTotal, $id]);
    }

    private function gerarModelPorRow(object $row): OrdemServicoModel {
        return new OrdemServicoModel(
            id: intval($row->id),
            idCliente: intval($row->id_cliente),
            idVeiculo: intval($row->id_veiculo),
            situacao: SituacaoOrdemServicoEnum::from($row->situacao),
            valorTotal: floatval($row->valor_total ?? 0),
            dataSolicitacao: new DateTime($row->data_solicitacao),
            dataAprovacao: $row->data_aprovacao !== null ? new DateTime($row->data_aprovacao) : null,
        );
    }
}

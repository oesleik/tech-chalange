<?php

declare(strict_types=1);

namespace App\OrdemServico\Service;

use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\FiltroOrdemServico;
use App\Core\AppDatabase;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use DateTime;
use InvalidArgumentException;

class OrdemServicoService {
    public function __construct(
        private AppDatabase $pdo
    ) {}

    /** @return OrdemServicoModel[] */
    public function listarOrdensServico(FiltroOrdemServico $filtros): array {
        [$queryFilters, $params] = $this->makeQueryFilters($filtros);

        if (count($params)) {
            $queryFilters = "WHERE $queryFilters";
        }

        $query = "SELECT * FROM ordens_servico $queryFilters ORDER BY data_solicitacao DESC ";

        if ($filtros->getLimit() > 0) {
            $query .= "LIMIT " . $filtros->getLimit();
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        $ordensServico = [];
        while ($row = $stmt->fetchObject()) {
            $ordensServico[] = $this->gerarModelPorRow($row);
        }

        return $ordensServico;
    }

    /** @return array{string,mixed} [$query, $params] */
    private function makeQueryFilters(FiltroOrdemServico $filtros): array {
        $filters = $params = [];

        if ($filtros->getSituacao() !== null) {
            $filters[] = "situacao = ?";
            $params[] = $filtros->getSituacao()->value;
        }

        if ($filtros->getIdCliente() !== null) {
            $filters[] = "id_cliente = ?";
            $params[] = $filtros->getIdCliente();
        }

        if ($filtros->getIdVeiculo() !== null) {
            $filters[] = "id_veiculo = ?";
            $params[] = $filtros->getIdVeiculo();
        }

        return [implode(" AND ", $filters), $params];
    }

    public function obterOrdemServicoPorId(int $id): ?OrdemServicoModel {
        $stmt = $this->pdo->prepare("SELECT * FROM ordens_servico WHERE id = ?");
        $stmt->execute([$id]);
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
            $dataAprovacao = new DateTime();
            $sql .= ", data_aprovacao = ?";
            $params[] = $dataAprovacao->format('Y-m-d H:i:s');
            $ordemServico = $ordemServico->withDataAprovacao($dataAprovacao);
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

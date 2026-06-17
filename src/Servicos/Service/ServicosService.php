<?php

declare(strict_types=1);

namespace App\Servicos\Service;

use App\Servicos\Model\ServicoModel;
use App\Core\AppDatabase;
use PDO;

class ServicosService {
    public function __construct(
        private AppDatabase $pdo
    ) {}

    /** @return ServicoModel[] */
    public function listarServicos(): array {
        $result = $this->pdo->query("SELECT * FROM servicos", PDO::FETCH_OBJ);
        $servicos = [];

        foreach ($result as $row) {
            $servicos[] = $this->gerarModelPorRow($row);
        }

        return $servicos;
    }

    public function obterServicoPorId(int $id): ?ServicoModel {
        $stmt = $this->pdo->prepare("SELECT * FROM servicos WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetchObject();
        return $result ? $this->gerarModelPorRow($result) : null;
    }

    public function criarServico(ServicoModel $servico): ServicoModel {
        $stmt = $this->pdo->prepare("INSERT INTO servicos (descricao, valor_unitario) VALUES (?, ?)");

        $stmt->execute([
            $servico->getDescricao(),
            $servico->getValorUnitario(),
        ]);

        $id = intval($this->pdo->lastInsertId());
        return $servico->withId($id);
    }

    public function atualizarServico(ServicoModel $servico): void {
        $camposParaAtualizar = [];
        $valores = [];

        $valoresModel = [
            "descricao"      => $servico->getDescricao(),
            "valor_unitario" => $servico->getValorUnitario(),
        ];

        foreach ($valoresModel as $campo => $valor) {
            $camposParaAtualizar[] = "`$campo` = ?";
            $valores[] = $valor;
        }

        $stmtCampos = implode(", ", $camposParaAtualizar);
        $stmt = $this->pdo->prepare("UPDATE servicos SET $stmtCampos WHERE id = ?");

        $valores[] = $servico->getId();
        $stmt->execute($valores);
    }

    private function gerarModelPorRow(object $row): ServicoModel {
        return new ServicoModel(
            id: $row->id,
            descricao: $row->descricao,
            valorUnitario: floatval($row->valor_unitario),
        );
    }
}

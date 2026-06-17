<?php

declare(strict_types=1);

namespace App\Peca\Service;

use App\Peca\Model\PecaModel;
use App\Peca\ValueObject\DescricaoValue;
use App\Peca\ValueObject\ValorUnitarioValue;
use App\Core\AppDatabase;
use PDO;

class PecaService {
    public function __construct(
        private AppDatabase $pdo
    ) {}

    /** @return PecaModel[] */
    public function listarPecas(): array {
        $result = $this->pdo->query("SELECT * FROM pecas", PDO::FETCH_OBJ);
        $pecas = [];

        foreach ($result as $row) {
            $pecas[] = $this->gerarModelPorRow($row);
        }

        return $pecas;
    }

    public function obterPecaPorId(int $id): ?PecaModel {
        $stmt = $this->pdo->prepare("SELECT * FROM pecas WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetchObject();
        return $result ? $this->gerarModelPorRow($result) : null;
    }

    public function criarPeca(PecaModel $peca): PecaModel {
        $stmt = $this->pdo->prepare("INSERT INTO pecas (descricao, valor_unitario) VALUES (?, ?)");

        $stmt->execute([
            $peca->getDescricao()->getValue(),
            $peca->getValorUnitario()->getValue(),
        ]);

        $id = intval($this->pdo->lastInsertId());
        return $peca->withId($id);
    }

    public function atualizarPeca(PecaModel $peca): void {
        $camposParaAtualizar = [];
        $valores = [];

        $valoresModel = [
            "descricao"      => $peca->getDescricao()->getValue(),
            "valor_unitario" => $peca->getValorUnitario()->getValue(),
        ];

        foreach ($valoresModel as $campo => $valor) {
            $camposParaAtualizar[] = "`$campo` = ?";
            $valores[] = $valor;
        }

        $stmtCampos = implode(", ", $camposParaAtualizar);
        $stmt = $this->pdo->prepare("UPDATE pecas SET $stmtCampos WHERE id = ?");

        $valores[] = $peca->getId();
        $stmt->execute($valores);
    }

    private function gerarModelPorRow(object $row): PecaModel {
        return new PecaModel(
            id: $row->id,
            descricao: new DescricaoValue($row->descricao),
            valorUnitario: new ValorUnitarioValue(floatval($row->valor_unitario)),
        );
    }
}

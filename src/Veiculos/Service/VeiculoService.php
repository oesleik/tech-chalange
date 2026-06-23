<?php

declare(strict_types=1);

namespace App\Veiculos\Service;

use App\Core\AppDatabase;
use App\Veiculos\Model\VeiculoModel;
use PDO;

class VeiculoService {
    public function __construct(
        private AppDatabase $pdo
    ) {}

    /** @return VeiculoModel[] */
    public function listarVeiculos(): array {
        $result = $this->pdo->query("SELECT * FROM veiculos", PDO::FETCH_OBJ);
        $veiculos = [];

        foreach ($result as $row) {
            $veiculos[] = $this->gerarModelPorRow($row);
        }

        return $veiculos;
    }

    public function obterVeiculoPorId(int $id): ?VeiculoModel {
        $stmt = $this->pdo->prepare("SELECT * FROM veiculos WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetchObject();
        return $result ? $this->gerarModelPorRow($result) : null;
    }

    public function obterVeiculoPorPlaca(string $placa): ?VeiculoModel {
        $stmt = $this->pdo->prepare("SELECT * FROM veiculos WHERE UPPER(REPLACE(placa, '-', '')) = ?");
        $stmt->execute([$placa]);
        $result = $stmt->fetchObject();
        return $result ? $this->gerarModelPorRow($result) : null;
    }

    public function criarVeiculo(VeiculoModel $veiculo): VeiculoModel {
        $stmt = $this->pdo->prepare("INSERT INTO veiculos (placa, marca, modelo) VALUES (?, ?, ?)");
        $stmt->execute([
            $veiculo->getPlaca(),
            $veiculo->getMarca(),
            $veiculo->getModelo(),
        ]);
        $id = intval($this->pdo->lastInsertId());
        return $veiculo->withId($id);
    }

    public function atualizarVeiculo(VeiculoModel $veiculo): void {
        $camposParaAtualizar = [];
        $valores = [];
        $valoresModel = [
            "placa" => $veiculo->getPlaca(),
            "marca" => $veiculo->getMarca(),
            "modelo" => $veiculo->getModelo(),
        ];
        foreach ($valoresModel as $campo => $valor) {
            $camposParaAtualizar[] = "`$campo` = ?";
            $valores[] = $valor;
        }
        $stmtCampos = implode(", ", $camposParaAtualizar);
        $stmt = $this->pdo->prepare("UPDATE veiculos SET $stmtCampos WHERE id = ?");
        $valores[] = $veiculo->getId();
        $stmt->execute($valores);
    }

    private function gerarModelPorRow(object $row): VeiculoModel {
        return new VeiculoModel(
            id: $row->id,
            placa: $row->placa,
            marca: $row->marca,
            modelo: $row->modelo,
        );
    }

}

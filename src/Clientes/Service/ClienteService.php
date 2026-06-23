<?php

declare(strict_types=1);

namespace App\Clientes\Service;

use App\Clientes\Model\ClienteModel;
use App\Clientes\ValueObject\CpfOrCnpjValueFactory;
use App\Clientes\ValueObject\EmailValue;
use App\Clientes\ValueObject\TelefoneValue;
use App\Core\AppDatabase;
use PDO;

class ClienteService {
    public function __construct(
        private AppDatabase $pdo
    ) {}

    /** @return ClienteModel[] */
    public function listarClientes(?string $cpfCnpj = null): array {
        if ($cpfCnpj === null) {
            $result = $this->pdo->query("SELECT * FROM clientes", PDO::FETCH_OBJ);
            $clientes = [];

            foreach ($result as $row) {
                $clientes[] = $this->gerarModelPorRow($row);
            }

            return $clientes;
        }

        // Normalizar: remover caracteres não numéricos
        $clean = preg_replace('/\D/', '', $cpfCnpj) ?: $cpfCnpj;
        $stmt = $this->pdo->prepare("SELECT * FROM clientes WHERE cpf_cnpj = ?");
        $stmt->execute([$clean]);

        $clientes = [];
        while ($row = $stmt->fetchObject()) {
            $clientes[] = $this->gerarModelPorRow($row);
        }

        return $clientes;
    }

    public function obterClientePorId(int $id): ?ClienteModel {
        $stmt = $this->pdo->prepare("SELECT * FROM clientes WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetchObject();
        return $result ? $this->gerarModelPorRow($result) : null;
    }

    public function criarCliente(ClienteModel $cliente): ClienteModel {
        $stmt = $this->pdo->prepare("INSERT INTO clientes (nome, cpf_cnpj, email, telefone) VALUES (?, ?, ?, ?)");

        $stmt->execute([
            $cliente->getNome(),
            $cliente->getCpfCnpj()->getValue(),
            $cliente->getEmail()->getValue(),
            $cliente->getTelefone()->getValue(),
        ]);

        $id = intval($this->pdo->lastInsertId());
        return $cliente->withId($id);
    }

    public function atualizarCliente(ClienteModel $cliente): void {
        $camposParaAtualizar = [];
        $valores = [];

        $valoresModel = [
            "nome" => $cliente->getNome(),
            "cpf_cnpj" => $cliente->getCpfCnpj()->getValue(),
            "email" => $cliente->getEmail()->getValue(),
            "telefone" => $cliente->getTelefone()->getValue(),
        ];

        foreach ($valoresModel as $campo => $valor) {
            $camposParaAtualizar[] = "`$campo` = ?";
            $valores[] = $valor;
        }

        $stmtCampos = implode(", ", $camposParaAtualizar);
        $stmt = $this->pdo->prepare("UPDATE clientes SET $stmtCampos WHERE id = ?");

        $valores[] = $cliente->getId();
        $stmt->execute($valores);
    }

    private function gerarModelPorRow(object $row): ClienteModel {
        return new ClienteModel(
            id: $row->id,
            nome: $row->nome,
            cpfCnpj: CpfOrCnpjValueFactory::make($row->cpf_cnpj),
            email: new EmailValue($row->email),
            telefone: new TelefoneValue($row->telefone),
        );
    }
}

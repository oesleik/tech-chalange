<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Persistence;

use PDO;

final class DatabaseConnection implements DbConnectionInterface {
    public function __construct(
        private readonly PDO $connection,
    ) {}

    public function buscarPorParametros(string $tabela, ?array $colunas, array $condicoes): array {
        $selectColunas = $colunas ? implode(', ', array_map($this->quoteIdentifier(...), $colunas)) : '*';
        [$where, $valores] = $this->montarWhere($condicoes);

        $sql = "SELECT {$selectColunas} FROM {$this->quoteIdentifier($tabela)} {$where}";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($valores);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function buscarTodos(string $tabela, ?array $colunas = null): array {
        $selectColunas = $colunas ? implode(', ', array_map($this->quoteIdentifier(...), $colunas)) : '*';
        $sql = "SELECT {$selectColunas} FROM {$this->quoteIdentifier($tabela)}";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function inserir(string $tabela, array $dados): int {
        $campos = array_keys($dados);
        $placeholders = implode(', ', array_fill(0, count($campos), '?'));
        $colunasSql = implode(', ', array_map($this->quoteIdentifier(...), $campos));

        $sql = "INSERT INTO {$this->quoteIdentifier($tabela)} ({$colunasSql}) VALUES ({$placeholders})";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute(array_values($dados));

        return (int) $this->connection->lastInsertId();
    }

    public function atualizar(string $tabela, array $dados, array $condicoes): void {
        $sets = implode(', ', array_map(
            fn(string $campo) => "{$this->quoteIdentifier($campo)} = ?",
            array_keys($dados)
        ));

        [$where, $valoresWhere] = $this->montarWhere($condicoes);

        $sql = "UPDATE {$this->quoteIdentifier($tabela)} SET {$sets} {$where}";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([...array_values($dados), ...$valoresWhere]);
    }

    public function deletar(string $tabela, array $condicoes): void {
        [$where, $valores] = $this->montarWhere($condicoes);

        $sql = "DELETE FROM {$this->quoteIdentifier($tabela)} {$where}";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($valores);
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function montarWhere(array $condicoes): array {
        if (empty($condicoes)) {
            return ['', []];
        }

        $clausulas = array_map(
            fn(string $campo) => "{$this->quoteIdentifier($campo)} = ?",
            array_keys($condicoes)
        );

        return ['WHERE ' . implode(' AND ', $clausulas), array_values($condicoes)];
    }

    private function quoteIdentifier(string $identifier): string {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}

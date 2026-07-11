<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Persistence;

interface DbConnectionInterface {
    /**
     * Busca linhas que satisfazem condições de igualdade simples.
     *
     * @param string[]|null $colunas  Colunas a selecionar (null = todas)
     * @param array<string, mixed> $condicoes  Pares campo => valor (AND entre eles)
     * @return array<int, array<string, mixed>>
     */
    public function buscarPorParametros(string $tabela, ?array $colunas, array $condicoes): array;

    /**
     * @param string[]|null $colunas
     * @return array<int, array<string, mixed>>
     */
    public function buscarTodos(string $tabela, ?array $colunas = null): array;

    /**
     * @param array<string, mixed> $dados  Pares campo => valor a inserir
     * @return int  ID gerado (auto-increment)
     */
    public function inserir(string $tabela, array $dados): int;

    /**
     * @param array<string, mixed> $dados       Campos a atualizar
     * @param array<string, mixed> $condicoes   Condições de igualdade (WHERE)
     */
    public function atualizar(string $tabela, array $dados, array $condicoes): void;

    /**
     * @param array<string, mixed> $condicoes
     */
    public function deletar(string $tabela, array $condicoes): void;
}

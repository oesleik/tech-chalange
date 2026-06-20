<?php

declare(strict_types=1);

namespace App\Estoque\Repository;

use App\Core\AppDatabase;
use RuntimeException;

class EstoqueRepository {
    public function __construct(private readonly AppDatabase $db) {}

    public function registrarEntrada(int $id_peca, int $quantidade): array {
        $peca = $this->buscarPecaOuFalha($id_peca);

        $this->inserirLancamento($id_peca, $quantidade, 'entrada');

        return [
            'id'              => (int) $this->db->lastInsertId(),
            'id_peca'         => $id_peca,
            'peca'            => $peca['descricao'],
            'quantidade'      => $quantidade,
            'tipo_lancamento' => 'entrada',
        ];
    }

    public function registrarBaixa(int $id_peca, int $quantidade): array {
        $peca = $this->buscarPecaOuFalha($id_peca);

        $estoqueAtual = $this->calcularEstoqueAtual($id_peca);

        if ($quantidade > $estoqueAtual) {
            throw new RuntimeException(
                "Estoque insuficiente. Disponível: {$estoqueAtual}, solicitado: {$quantidade}.",
                409
            );
        }

        $this->inserirLancamento($id_peca, $quantidade, 'baixa');

        return [
            'id'              => (int) $this->db->lastInsertId(),
            'id_peca'         => $id_peca,
            'peca'            => $peca['descricao'],
            'quantidade'      => $quantidade,
            'tipo_lancamento' => 'baixa',
            'estoque_atual'   => $estoqueAtual - $quantidade,
        ];
    }

    public function consultarEstoquePorPeca(int $id_peca): array {
        $peca = $this->buscarPecaOuFalha($id_peca, comValorUnitario: true);

        return [
            'id_peca'        => $peca['id'],
            'descricao'      => $peca['descricao'],
            'valor_unitario' => (float) $peca['valor_unitario'],
            'estoque_atual'  => $this->calcularEstoqueAtual($id_peca),
        ];
    }

    private function buscarPecaOuFalha(int $id_peca, bool $comValorUnitario = false): array {
        $campos = $comValorUnitario ? 'id, descricao, valor_unitario' : 'id, descricao';

        $stmt = $this->db->prepare("SELECT {$campos} FROM pecas WHERE id = :id");
        $stmt->execute(['id' => $id_peca]);
        $peca = $stmt->fetch();

        if (!$peca) {
            throw new RuntimeException("Peça com ID {$id_peca} não encontrada.", 404);
        }

        return $peca;
    }

    private function calcularEstoqueAtual(int $id_peca): int {
        $stmt = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN tipo_lancamento = 'entrada' THEN quantidade ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN tipo_lancamento = 'baixa'  THEN quantidade ELSE 0 END), 0)
                AS estoque_atual
             FROM estoque
             WHERE id_peca = :id_peca"
        );
        $stmt->execute(['id_peca' => $id_peca]);
        $resultado = $stmt->fetch();

        return (int) $resultado['estoque_atual'];
    }

    private function inserirLancamento(int $id_peca, int $quantidade, string $tipo): void {
        $stmt = $this->db->prepare(
            'INSERT INTO estoque (id_peca, quantidade, tipo_lancamento)
             VALUES (:id_peca, :quantidade, :tipo_lancamento)'
        );
        $stmt->execute([
            'id_peca'         => $id_peca,
            'quantidade'      => $quantidade,
            'tipo_lancamento' => $tipo,
        ]);
    }
}

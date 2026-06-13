<?php

declare(strict_types=1);

namespace App\Estoque\Repository;

use App\Core\AppDatabase;
use RuntimeException;

class EstoqueRepository {
    public function __construct(private readonly AppDatabase $db) {}

    public function registrarEntrada(int $id_peca, int $quantidade): array {
        $stmt = $this->db->prepare('SELECT id, descricao FROM pecas WHERE id = :id');
        $stmt->execute(['id' => $id_peca]);
        $peca = $stmt->fetch();

        if (!$peca) {
            throw new RuntimeException("Peça com ID {$id_peca} não encontrada.", 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO estoque (id_peca, quantidade, tipo_lancamento)
             VALUES (:id_peca, :quantidade, :tipo_lancamento)'
        );

        $stmt->execute([
            'id_peca'         => $id_peca,
            'quantidade'      => $quantidade,
            'tipo_lancamento' => 'entrada',
        ]);

        return [
            'id'              => (int) $this->db->lastInsertId(),
            'id_peca'         => $id_peca,
            'peca'            => $peca['descricao'],
            'quantidade'      => $quantidade,
            'tipo_lancamento' => 'entrada',
        ];
    }
}

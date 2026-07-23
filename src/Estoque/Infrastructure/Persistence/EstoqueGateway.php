<?php

declare(strict_types=1);

namespace App\Estoque\Infrastructure\Persistence;

use App\Core\AppDatabase;
use App\Estoque\Application\Gateway\EstoqueGatewayInterface;
use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;

final class EstoqueGateway implements EstoqueGatewayInterface {
    public function __construct(private readonly AppDatabase $db) {}

    public function pecaExiste(int $pecaId): bool {
        $stmt = $this->db->prepare('SELECT id FROM pecas WHERE id = :id');
        $stmt->execute(['id' => $pecaId]);
        return (bool) $stmt->fetch();
    }

    // o estoque é calculado somando entradas e subtraindo baixas — não tem coluna de saldo
    public function calcularEstoqueAtual(int $pecaId): int {
        $stmt = $this->db->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN tipo_lancamento = :entrada THEN quantidade ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN tipo_lancamento = :baixa  THEN quantidade ELSE 0 END), 0)
                AS estoque_atual
            FROM estoque
            WHERE id_peca = :id_peca'
        );
        $stmt->execute([
            'entrada'  => TipoLancamentoEnum::ENTRADA->value,
            'baixa'    => TipoLancamentoEnum::BAIXA->value,
            'id_peca'  => $pecaId,
        ]);

        return (int) $stmt->fetch()['estoque_atual'];
    }

    public function inserirLancamento(int $pecaId, int $quantidade, TipoLancamentoEnum $tipo): LancamentoEstoque {
        $stmt = $this->db->prepare(
            'INSERT INTO estoque (id_peca, quantidade, tipo_lancamento) VALUES (?, ?, ?)'
        );
        $stmt->execute([$pecaId, $quantidade, $tipo->value]);

        return LancamentoEstoque::reconstituir(
            id: (int) $this->db->lastInsertId(),
            pecaId: $pecaId,
            quantidade: $quantidade,
            tipo: $tipo,
        );
    }
}

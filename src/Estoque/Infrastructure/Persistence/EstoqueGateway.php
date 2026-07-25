<?php

declare(strict_types=1);

namespace App\Estoque\Infrastructure\Persistence;

use App\Core\AppDatabase;
use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use App\Estoque\Application\Gateway\EstoqueGatewayInterface;
use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;

final class EstoqueGateway implements EstoqueGatewayInterface {
    private const TABELA_ESTOQUE = 'estoque';
    private const TABELA_PECAS   = 'pecas';

    public function __construct(
        private readonly DbConnectionInterface $connection,
        private readonly AppDatabase $db, // necessário só para o calcularEstoqueAtual (SUM/CASE)
    ) {}

    public function pecaExiste(int $pecaId): bool {
        $linhas = $this->connection->buscarPorParametros(
            self::TABELA_PECAS,
            ['id'],
            ['id' => $pecaId]
        );

        return !empty($linhas);
    }

    // esse fica com SQL manual — o SUM/CASE é complexo demais para abstrair via DbConnectionInterface
    public function calcularEstoqueAtual(int $pecaId): int {
        $stmt = $this->db->prepare(
            'SELECT
                COALESCE(SUM(CASE WHEN tipo_lancamento = :entrada THEN quantidade ELSE 0 END), 0) -
                COALESCE(SUM(CASE WHEN tipo_lancamento = :baixa   THEN quantidade ELSE 0 END), 0)
                AS estoque_atual
            FROM estoque
            WHERE id_peca = :id_peca'
        );
        $stmt->execute([
            'entrada' => TipoLancamentoEnum::ENTRADA->value,
            'baixa'   => TipoLancamentoEnum::BAIXA->value,
            'id_peca' => $pecaId,
        ]);

        return (int) $stmt->fetch()['estoque_atual'];
    }

    public function inserirLancamento(int $pecaId, int $quantidade, TipoLancamentoEnum $tipo): LancamentoEstoque {
        $id = $this->connection->inserir(self::TABELA_ESTOQUE, [
            'id_peca'         => $pecaId,
            'quantidade'      => $quantidade,
            'tipo_lancamento' => $tipo->value,
        ]);

        return LancamentoEstoque::reconstituir($id, $pecaId, $quantidade, $tipo);
    }
}

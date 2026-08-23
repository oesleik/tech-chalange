<?php

declare(strict_types=1);

namespace App\OrdemServico\Infrastructure\Persistence;

use App\Core\AppDatabase;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Domain\ValueObject\FiltroOrdemServico;

final class OrdemServicoGateway implements OrdemServicoGatewayInterface {
    private const TABELA = 'ordens_servico';

    public function __construct(
        private readonly AppDatabase $pdo,
    ) {}

    public function buscarPorId(int $id): ?OrdemServico {
        $stmt = $this->pdo->prepare('SELECT * FROM ' . self::TABELA . ' WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? OrdemServicoMapper::paraEntidade($row) : null;
    }

    public function listar(FiltroOrdemServico $filtro): array {
        [$where, $params] = $this->montarWhere($filtro);
        $queryWhere = $where ? "WHERE $where" : '';

        $query = "SELECT * FROM " . self::TABELA . " $queryWhere ORDER BY data_solicitacao DESC";

        if ($filtro->limit > 0) {
            $query .= ' LIMIT ' . $filtro->limit;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);

        $resultado = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $resultado[] = OrdemServicoMapper::paraEntidade($row);
        }

        return $resultado;
    }

    public function inserir(OrdemServico $ordemServico): OrdemServico {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ' . self::TABELA . ' (id_cliente, id_veiculo, situacao, valor_total, data_solicitacao)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $ordemServico->idCliente(),
            $ordemServico->idVeiculo(),
            $ordemServico->situacao()->value,
            $ordemServico->valorTotal(),
            $ordemServico->dataSolicitacao()->format('Y-m-d H:i:s'),
        ]);

        return $ordemServico->comId((int) $this->pdo->lastInsertId());
    }

    public function atualizarSituacao(OrdemServico $ordemServico): OrdemServico {
        $sql    = 'UPDATE ' . self::TABELA . ' SET situacao = ?';
        $params = [$ordemServico->situacao()->value];

        if ($ordemServico->dataAprovacao() !== null) {
            $sql      .= ', data_aprovacao = ?';
            $params[]  = $ordemServico->dataAprovacao()->format('Y-m-d H:i:s');
        }

        $sql      .= ' WHERE id = ?';
        $params[]  = $ordemServico->id();

        $this->pdo->prepare($sql)->execute($params);

        return $ordemServico;
    }

    public function atualizarValorTotal(int $id, float $valorTotal): void {
        $this->pdo->prepare('UPDATE ' . self::TABELA . ' SET valor_total = ? WHERE id = ?')
            ->execute([$valorTotal, $id]);
    }

    public function obterProximaNaFila(): ?OrdemServico {
        // prioridade: aprovada → recebida
        $stmt = $this->pdo->prepare(
            'SELECT * FROM ' . self::TABELA . ' WHERE situacao = ? ORDER BY data_aprovacao ASC LIMIT 1'
        );
        $stmt->execute([SituacaoOrdemServicoEnum::APROVADA->value]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM ' . self::TABELA . ' WHERE situacao = ? ORDER BY data_solicitacao ASC LIMIT 1'
            );
            $stmt->execute([SituacaoOrdemServicoEnum::RECEBIDA->value]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        return $row ? OrdemServicoMapper::paraEntidade($row) : null;
    }

    /** @return array{string, array<mixed>} */
    private function montarWhere(FiltroOrdemServico $filtro): array {
        $conds  = [];
        $params = [];

        if ($filtro->situacao !== null) {
            $conds[]  = 'situacao = ?';
            $params[] = $filtro->situacao->value;
        }
        if ($filtro->idCliente !== null) {
            $conds[]  = 'id_cliente = ?';
            $params[] = $filtro->idCliente;
        }
        if ($filtro->idVeiculo !== null) {
            $conds[]  = 'id_veiculo = ?';
            $params[] = $filtro->idVeiculo;
        }

        return [implode(' AND ', $conds), $params];
    }
}

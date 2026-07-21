<?php

declare(strict_types=1);

namespace App\Peca\Infrastructure\Persistence;

use App\Peca\Application\Gateway\PecaGatewayInterface;
use App\Peca\Domain\Entity\Peca;
use App\Core\Infrastructure\Persistence\DbConnectionInterface;

final class PecaGateway implements PecaGatewayInterface {
    private const TABELA = 'pecas';

    public function __construct(private readonly DbConnectionInterface $connection) {}

    public function buscarPorId(int $id): ?Peca {
        $linhas = $this->connection->buscarPorParametros(self::TABELA, null, ['id' => $id]);
        return $linhas === [] ? null : PecaMapper::paraEntidade($linhas[0]);
    }

    public function inserir(Peca $peca): Peca {
        $id = $this->connection->inserir(self::TABELA, [
            'descricao' => $peca->descricao(),
            'valor_unitario' => $peca->valorUnitario()->getValue(),
        ]);
        return $peca->comId($id);
    }

    public function atualizar(Peca $peca): Peca {
        $this->connection->atualizar(
            self::TABELA,
            [
                'descricao' => $peca->descricao(),
                'valor_unitario' => $peca->valorUnitario()->getValue(),
            ],
            ['id' => $peca->id()],
        );
        return $peca;
    }

    public function listar(): array {
        $linhas = $this->connection->buscarTodos(self::TABELA);
        return array_map(PecaMapper::paraEntidade(...), $linhas);
    }
}

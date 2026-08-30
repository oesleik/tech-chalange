<?php

declare(strict_types=1);

namespace App\Servicos\Infrastructure\Persistence;

use App\Servicos\Application\Gateway\ServicoGatewayInterface;
use App\Servicos\Domain\Entity\Servico;
use App\Core\Infrastructure\Persistence\DbConnectionInterface;

final class ServicoGateway implements ServicoGatewayInterface {
    private const TABELA = 'servicos';

    public function __construct(private readonly DbConnectionInterface $connection) {}

    public function buscarPorId(int $id): ?Servico {
        $linhas = $this->connection->buscarPorParametros(self::TABELA, null, ['id' => $id]);
        return $linhas === [] ? null : ServicoMapper::paraEntidade($linhas[0]);
    }

    public function inserir(Servico $servico): Servico {
        $id = $this->connection->inserir(self::TABELA, [
            'descricao' => $servico->descricao(),
            'valor_unitario' => $servico->valorUnitario()->getValue(),
        ]);
        return $servico->comId($id);
    }

    public function atualizar(Servico $servico): Servico {
        $this->connection->atualizar(
            self::TABELA,
            [
                'descricao' => $servico->descricao(),
                'valor_unitario' => $servico->valorUnitario()->getValue(),
            ],
            ['id' => $servico->id()],
        );
        return $servico;
    }

    public function listar(): array {
        $linhas = $this->connection->buscarTodos(self::TABELA);
        return array_map(ServicoMapper::paraEntidade(...), $linhas);
    }
}

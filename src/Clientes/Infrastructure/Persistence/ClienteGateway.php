<?php

declare(strict_types=1);

namespace App\Clientes\Infrastructure\Persistence;

use App\Clientes\Application\Gateway\ClienteGatewayInterface;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\ValueObject\Cnpj;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Core\Infrastructure\Persistence\DbConnectionInterface;

final class ClienteGateway implements ClienteGatewayInterface {
    private const TABELA = 'clientes';

    public function __construct(
        private readonly DbConnectionInterface $connection,
    ) {}

    public function buscarPorId(int $id): ?Cliente {
        $linhas = $this->connection->buscarPorParametros(self::TABELA, null, ['id' => $id]);

        if ($linhas === []) {
            return null;
        }

        return ClienteMapper::paraEntidade($linhas[0]);
    }

    public function buscarPorCpfCnpj(Cpf|Cnpj $cpfCnpj): ?Cliente {
        $linhas = $this->connection->buscarPorParametros(
            self::TABELA,
            null,
            ['cpf_cnpj' => $cpfCnpj->getValue()],
        );

        if ($linhas === []) {
            return null;
        }

        return ClienteMapper::paraEntidade($linhas[0]);
    }

    public function inserir(Cliente $cliente): Cliente {
        $id = $this->connection->inserir(self::TABELA, [
            'nome' => $cliente->nome(),
            'cpf_cnpj' => $cliente->cpfCnpj()->getValue(),
            'email' => $cliente->email()->getValue(),
            'telefone' => $cliente->telefone()->getValue(),
        ]);

        return $cliente->comId($id);
    }

    public function atualizar(Cliente $cliente): Cliente {
        $this->connection->atualizar(
            self::TABELA,
            [
                'nome' => $cliente->nome(),
                'cpf_cnpj' => $cliente->cpfCnpj()->getValue(),
                'email' => $cliente->email()->getValue(),
                'telefone' => $cliente->telefone()->getValue(),
            ],
            ['id' => $cliente->id()],
        );

        return $cliente;
    }

    public function listar(Cpf|Cnpj|null $cpfCnpj = null): array {
        $linhas = $cpfCnpj === null
            ? $this->connection->buscarTodos(self::TABELA)
            : $this->connection->buscarPorParametros(self::TABELA, null, ['cpf_cnpj' => $cpfCnpj->getValue()]);

        return array_map(static fn(array $linha) => ClienteMapper::paraEntidade($linha), $linhas);
    }
}

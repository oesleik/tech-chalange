<?php

declare(strict_types=1);

namespace App\Veiculos\Infrastructure\Persistence;

use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use App\Veiculos\Infrastructure\Persistence\VeiculoGatewayInterface;
use App\Veiculos\Domain\Entity\Veiculo;

final class VeiculoGateway implements VeiculoGatewayInterface {
    private const TABELA = 'veiculos';

    public function __construct(
        private readonly DbConnectionInterface $connection,
    ) {}

    public function buscarPorId(int $id): ?Veiculo {
        $linhas = $this->connection->buscarPorParametros(
            self::TABELA,
            null,
            ['id' => $id]
        );

        if (empty($linhas)) {
            return null;
        }

        return VeiculoMapper::paraEntidade($linhas[0]);
    }
}

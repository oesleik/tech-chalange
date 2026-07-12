<?php

declare(strict_types=1);

namespace App\Veiculos\Infrastructure\Persistence;

use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use App\Veiculos\Domain\Entity\Placa;
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

    public function buscarPorPlaca(Placa $placa): ?Veiculo {
        $linhas = $this->connection->buscarPorParametros(
            self::TABELA,
            null,
            ['placa' => $placa->getValue()]
        );

        if (empty($linhas)) {
            return null;
        }

        return VeiculoMapper::paraEntidade($linhas[0]);
    }

    public function inserir(Veiculo $veiculo): Veiculo {
        $idVeiculo = $this->connection->inserir(
            self::TABELA,
            [
                'placa' => $veiculo->placa()->getValue(),
                'marca' => $veiculo->marca(),
                'modelo' => $veiculo->modelo(),
            ]
        );

        return $veiculo->comId($idVeiculo);
    }
}

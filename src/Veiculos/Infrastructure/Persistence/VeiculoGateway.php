<?php

declare(strict_types=1);

namespace App\Veiculos\Infrastructure\Persistence;

use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use App\Veiculos\Application\UseCase\ListarVeiculo\FiltroListagemVeiculo;
use App\Veiculos\Domain\Entity\Placa;
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

    public function listar(FiltroListagemVeiculo $filtro): array {
        $registros = $this->connection->buscarComFiltro(
            tabela: self::TABELA,
            condicoesExatas: $this->condicoesExatas($filtro),
            condicoesParciais: $this->condicoesParciais($filtro),
            limite: $filtro->porPagina,
            offset: $filtro->offset(),
        );

        return array_map(
            static fn(array $registro) => VeiculoMapper::paraEntidade($registro),
            $registros,
        );
    }

    public function contar(FiltroListagemVeiculo $filtro): int {
        return $this->connection->contarComFiltro(
            tabela: self::TABELA,
            condicoesExatas: $this->condicoesExatas($filtro),
            condicoesParciais: $this->condicoesParciais($filtro),
        );
    }

    /** @return array<string, mixed> */
    private function condicoesExatas(FiltroListagemVeiculo $filtro): array {
        $condicoes = [];
        if ($filtro->placa !== null) {
            $condicoes['placa'] = $filtro->placa->getValue();
        }

        return $condicoes;
    }

    /** @return array<string, string> */
    private function condicoesParciais(FiltroListagemVeiculo $filtro): array {
        $condicoes = [];
        if ($filtro->marca !== null) {
            $condicoes['marca'] = $filtro->marca;
        }
        if ($filtro->modelo !== null) {
            $condicoes['modelo'] = $filtro->modelo;
        }

        return $condicoes;
    }

    public function atualizar(Veiculo $veiculo): Veiculo {
        $this->connection->atualizar(
            self::TABELA,
            [
                'placa' => $veiculo->placa()->getValue(),
                'marca' => $veiculo->marca(),
                'modelo' => $veiculo->modelo(),
            ],
            ['id' => $veiculo->id()],
        );

        return $veiculo;
    }
}

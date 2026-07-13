<?php

declare(strict_types=1);

namespace App\Veiculos\Infrastructure\Persistence;

use App\Veiculos\Application\UseCase\ListarVeiculo\FiltroListagemVeiculo;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;

interface VeiculoGatewayInterface {
    public function buscarPorId(int $id): ?Veiculo;

    public function buscarPorPlaca(Placa $placa): ?Veiculo;

    public function inserir(Veiculo $veiculo): Veiculo;

    /**
     * @return Veiculo[]
     */
    public function listar(FiltroListagemVeiculo $filtro): array;

    public function contar(FiltroListagemVeiculo $filtro): int;

    public function atualizar(Veiculo $veiculo): Veiculo;
}

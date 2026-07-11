<?php

declare(strict_types=1);

namespace App\Veiculos\Infrastructure\Persistence;

use App\Veiculos\Domain\Entity\Veiculo;

interface VeiculoGatewayInterface {
    public function buscarPorId(int $id): ?Veiculo;

    // TODO p´roximas rotas vem aqui
}

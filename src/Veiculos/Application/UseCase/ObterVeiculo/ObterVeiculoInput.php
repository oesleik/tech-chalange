<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\ObterVeiculo;

use InvalidArgumentException;

final class ObterVeiculoInput {
    public function __construct(
        public readonly int $id,
    ) {
        if ($id <= 0) {
            throw new InvalidArgumentException('O id do veículo deve ser maior que zero.');
        }
    }
}

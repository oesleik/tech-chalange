<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\EditarVeiculo;

final class EditarVeiculoInputDTO {
    public function __construct(
        public readonly ?string $placa = null,
        public readonly ?string $marca = null,
        public readonly ?string $modelo = null,
    ) {}
}

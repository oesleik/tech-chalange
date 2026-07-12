<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\CriarVeiculo;

use InvalidArgumentException;

final class CriarVeiculoInputDTO {
    public function __construct(
        public readonly string $placa,
        public readonly string $marca,
        public readonly string $modelo,
    ) {}

    public static function fromArray(array $data): self {
        if (empty($data['placa'])) {
            throw new InvalidArgumentException('Placa é obrigatória');
        }
        if (empty($data['marca'])) {
            throw new InvalidArgumentException('Marca é obrigatória');
        }
        if (empty($data['modelo'])) {
            throw new InvalidArgumentException('Modelo é obrigatório');
        }

        return new self(
            placa: trim($data['placa']),
            marca: trim($data['marca']),
            modelo: trim($data['modelo']),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\DTO;

use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use InvalidArgumentException;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CriarVeiculoInputDTO',
    required: ['placa', 'marca', 'modelo'],
    properties: [
        new OA\Property(property: 'placa', type: 'string', example: 'ABC1D23', maxLength: 8),
        new OA\Property(property: 'marca', type: 'string', example: 'Toyota'),
        new OA\Property(property: 'modelo', type: 'string', example: 'Corolla'),
    ]
)]
final class CriarVeiculoMapper {
    public static function parse(array $data): Veiculo {
        if (empty($data['placa'])) {
            throw new InvalidArgumentException('Placa é obrigatória');
        }
        if (empty($data['marca'])) {
            throw new InvalidArgumentException('Marca é obrigatória');
        }
        if (empty($data['modelo'])) {
            throw new InvalidArgumentException('Modelo é obrigatório');
        }

        return new Veiculo(
            id: 0,
            placa: new Placa(trim($data['placa'])),
            marca: trim($data['marca']),
            modelo: trim($data['modelo']),
        );
    }
}

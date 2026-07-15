<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\DTO;

use App\Veiculos\Application\UseCase\EditarVeiculo\EditarVeiculoInputDTO;
use App\Veiculos\Domain\Entity\Placa;
use InvalidArgumentException;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EditarVeiculoInputDTO',
    type: 'object',
    properties: [
        new OA\Property(property: 'placa', type: 'string', example: 'ABC1D23', nullable: true),
        new OA\Property(property: 'marca', type: 'string', example: 'Toyota', nullable: true),
        new OA\Property(property: 'modelo', type: 'string', example: 'Corolla', nullable: true),
    ]
)]
final class EditarVeiculoMapper {
    public static function parse(array $data): EditarVeiculoInputDTO {
        $campos = [];

        foreach (['placa', 'marca', 'modelo'] as $campo) {
            if (!array_key_exists($campo, $data) || $data[$campo] === null) {
                $campos[$campo] = null;
                continue;
            }

            if (!is_string($data[$campo])) {
                throw new InvalidArgumentException("Campo '{$campo}' deve ser uma string.");
            }

            $valor = trim($data[$campo]);
            $campos[$campo] = $valor === '' ? null : $valor;
        }

        return new EditarVeiculoInputDTO(
            placa: $campos['placa'],
            marca: $campos['marca'],
            modelo: $campos['modelo'],
        );
    }
}

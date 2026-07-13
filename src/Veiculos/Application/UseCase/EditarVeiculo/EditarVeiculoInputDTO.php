<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\EditarVeiculo;

use InvalidArgumentException;
use OpenApi\Attributes as OA;

#[OA\Schema(
    type: 'object'
)]
final class EditarVeiculoInputDTO {
    public function __construct(
        #[OA\Property(
            description: 'Placa do veículo',
            example: 'ABC1D23',
            nullable: true
        )]
        public readonly ?string $placa = null,
        #[OA\Property(
            description: 'Marca do veículo',
            example: 'Toyota',
            nullable: true
        )]
        public readonly ?string $marca = null,
        #[OA\Property(
            description: 'Modelo do veículo',
            example: 'Corolla',
            nullable: true
        )]
        public readonly ?string $modelo = null,
    ) {}

    public static function fromArray(array $data): self {
        return new self(
            placa: self::normalizar($data, 'placa'),
            marca: self::normalizar($data, 'marca'),
            modelo: self::normalizar($data, 'modelo'),
        );
    }

    private static function normalizar(array $data, string $campo): ?string {
        if (!array_key_exists($campo, $data) || $data[$campo] === null) {
            return null;
        }

        if (!is_string($data[$campo])) {
            throw new InvalidArgumentException("Campo '{$campo}' deve ser uma string.");
        }

        $valor = trim($data[$campo]);

        return $valor === '' ? null : $valor;
    }
}

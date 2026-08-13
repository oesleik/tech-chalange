<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\DTO;

use App\Clientes\Application\UseCase\EditarCliente\EditarClienteInputDTO;
use InvalidArgumentException;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EditarClienteInputDTO',
    properties: [
        new OA\Property(property: 'nome', type: 'string', example: 'Fulano de Tal', nullable: true),
        new OA\Property(property: 'cpf_cnpj', type: 'string', example: '123.456.789-09', nullable: true),
        new OA\Property(property: 'email', type: 'string', example: 'fulano@example.com', nullable: true),
        new OA\Property(property: 'telefone', type: 'string', example: '5412345678', nullable: true),
    ]
)]
final class EditarClienteMapper {
    public static function parse(array $data): EditarClienteInputDTO {
        $campos = [];

        foreach (['nome', 'cpf_cnpj', 'email', 'telefone'] as $campo) {
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

        return new EditarClienteInputDTO(
            nome: $campos['nome'],
            cpfCnpj: $campos['cpf_cnpj'],
            email: $campos['email'],
            telefone: $campos['telefone'],
        );
    }
}

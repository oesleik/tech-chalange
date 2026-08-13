<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\DTO;

use App\Clientes\Application\UseCase\CriarCliente\CriarClienteInputDTO;
use InvalidArgumentException;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CriarClienteInputDTO',
    required: ['nome', 'cpf_cnpj', 'email', 'telefone'],
    properties: [
        new OA\Property(property: 'nome', type: 'string', example: 'Fulano de Tal'),
        new OA\Property(property: 'cpf_cnpj', type: 'string', example: '123.456.789-09'),
        new OA\Property(property: 'email', type: 'string', example: 'fulano@example.com'),
        new OA\Property(property: 'telefone', type: 'string', example: '5412345678'),
    ]
)]
final class CriarClienteMapper {
    public static function parse(array $data): CriarClienteInputDTO {
        if (empty($data['nome']) || !is_string($data['nome'])) {
            throw new InvalidArgumentException('Nome é obrigatório.');
        }

        if (empty($data['cpf_cnpj']) || !is_string($data['cpf_cnpj'])) {
            throw new InvalidArgumentException('CPF/CNPJ é obrigatório.');
        }

        if (empty($data['email']) || !is_string($data['email'])) {
            throw new InvalidArgumentException('E-mail é obrigatório.');
        }

        if (empty($data['telefone']) || !is_string($data['telefone'])) {
            throw new InvalidArgumentException('Telefone é obrigatório.');
        }

        if (!preg_match('/^.{1,254}$/', $data['email'])) {
            throw new InvalidArgumentException('E-mail inválido.');
        }

        if (preg_match('/[^\d\(\)\s\+-]/', $data['telefone'])) {
            throw new InvalidArgumentException('Telefone deve conter apenas números e símbolos básicos.');
        }

        return new CriarClienteInputDTO(
            nome: trim($data['nome']),
            cpfCnpj: trim($data['cpf_cnpj']),
            email: trim($data['email']),
            telefone: trim($data['telefone']),
        );
    }
}

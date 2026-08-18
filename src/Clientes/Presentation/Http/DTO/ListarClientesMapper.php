<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\DTO;

use App\Clientes\Application\UseCase\ListarClientes\ListarClientesInputDTO;
use InvalidArgumentException;

final class ListarClientesMapper {
    public static function fromQueryParams(array $queryParams): ListarClientesInputDTO {
        if (!array_key_exists('cpf_cnpj', $queryParams) || $queryParams['cpf_cnpj'] === null || $queryParams['cpf_cnpj'] === '') {
            return new ListarClientesInputDTO();
        }

        if (!is_string($queryParams['cpf_cnpj'])) {
            throw new InvalidArgumentException('cpf_cnpj deve ser uma string.');
        }

        return new ListarClientesInputDTO(
            cpfCnpj: trim($queryParams['cpf_cnpj']),
        );
    }
}

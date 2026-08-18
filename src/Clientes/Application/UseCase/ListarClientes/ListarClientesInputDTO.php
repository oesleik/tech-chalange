<?php

declare(strict_types=1);

namespace App\Clientes\Application\UseCase\ListarClientes;

final class ListarClientesInputDTO {
    public function __construct(
        public readonly ?string $cpfCnpj = null,
    ) {}
}

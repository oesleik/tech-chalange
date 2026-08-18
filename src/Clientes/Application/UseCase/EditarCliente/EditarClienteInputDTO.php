<?php

declare(strict_types=1);

namespace App\Clientes\Application\UseCase\EditarCliente;

final class EditarClienteInputDTO {
    public function __construct(
        public readonly ?string $nome = null,
        public readonly ?string $cpfCnpj = null,
        public readonly ?string $email = null,
        public readonly ?string $telefone = null,
    ) {}
}

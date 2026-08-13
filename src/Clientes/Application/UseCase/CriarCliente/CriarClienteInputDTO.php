<?php

declare(strict_types=1);

namespace App\Clientes\Application\UseCase\CriarCliente;

final class CriarClienteInputDTO {
    public function __construct(
        public readonly string $nome,
        public readonly string $cpfCnpj,
        public readonly string $email,
        public readonly string $telefone,
    ) {}
}

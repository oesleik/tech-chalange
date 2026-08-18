<?php

declare(strict_types=1);

namespace App\Clientes\Application\UseCase\ListarClientes;

use App\Clientes\Domain\Entity\Cliente;

interface ListarClientesUseCaseInterface {
    /** @return array<int, Cliente> */
    public function executar(ListarClientesInputDTO $input): array;
}

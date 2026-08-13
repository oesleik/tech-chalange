<?php

declare(strict_types=1);

namespace App\Clientes\Application\UseCase\ListarClientes;

interface ListarClientesUseCaseInterface {
    /** @return array<int, object> */
    public function executar(ListarClientesInputDTO $input): array;
}

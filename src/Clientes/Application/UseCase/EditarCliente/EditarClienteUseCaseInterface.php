<?php

declare(strict_types=1);

namespace App\Clientes\Application\UseCase\EditarCliente;

use App\Clientes\Domain\Entity\Cliente;

interface EditarClienteUseCaseInterface {
    public function executar(int $id, EditarClienteInputDTO $input): Cliente;
}

<?php

declare(strict_types=1);

namespace App\Clientes\Application\UseCase\CriarCliente;

use App\Clientes\Domain\Entity\Cliente;

interface CriarClienteUseCaseInterface {
    public function executar(CriarClienteInputDTO $input): Cliente;
}

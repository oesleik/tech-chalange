<?php

declare(strict_types=1);

namespace App\Clientes\Application\UseCase\ObterCliente;

use App\Clientes\Domain\Entity\Cliente;

interface ObterClienteUseCaseInterface {
    public function executar(int $id): Cliente;
}

<?php

declare(strict_types=1);

namespace App\Clientes\Application\UseCase\ObterCliente;

use App\Clientes\Application\Gateway\ClienteGatewayInterface;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\Exception\ClienteNaoEncontradoException;

final class ObterClienteUseCase implements ObterClienteUseCaseInterface {
    public function __construct(
        private readonly ClienteGatewayInterface $gateway,
    ) {}

    public function executar(int $id): Cliente {
        return $this->gateway->buscarPorId($id)
            ?? throw ClienteNaoEncontradoException::comId($id);
    }
}

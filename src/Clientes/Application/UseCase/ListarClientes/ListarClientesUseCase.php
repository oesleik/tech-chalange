<?php

declare(strict_types=1);

namespace App\Clientes\Application\UseCase\ListarClientes;

use App\Clientes\Application\Gateway\ClienteGatewayInterface;
use App\Clientes\Domain\ValueObject\CpfOrCnpjValueFactory;

final class ListarClientesUseCase implements ListarClientesUseCaseInterface {
    public function __construct(
        private readonly ClienteGatewayInterface $gateway,
    ) {}

    public function executar(ListarClientesInputDTO $input): array {
        $cpfCnpj = $input->cpfCnpj !== null
            ? CpfOrCnpjValueFactory::make($input->cpfCnpj)
            : null;

        return $this->gateway->listar($cpfCnpj);
    }
}

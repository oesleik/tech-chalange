<?php

declare(strict_types=1);

namespace App\Clientes\Application\UseCase\CriarCliente;

use App\Clientes\Application\Gateway\ClienteGatewayInterface;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\Exception\ClienteJaCadastradoException;
use App\Clientes\Domain\ValueObject\CpfOrCnpjValueFactory;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;

final class CriarClienteUseCase implements CriarClienteUseCaseInterface {
    public function __construct(
        private readonly ClienteGatewayInterface $gateway,
    ) {}

    public function executar(CriarClienteInputDTO $input): Cliente {
        $cpfCnpj = CpfOrCnpjValueFactory::make($input->cpfCnpj);

        if ($this->gateway->buscarPorCpfCnpj($cpfCnpj) !== null) {
            throw ClienteJaCadastradoException::comCpfCnpj($cpfCnpj->getFormattedValue());
        }

        $cliente = Cliente::criar(
            nome: $input->nome,
            cpfCnpj: $cpfCnpj,
            email: new Email($input->email),
            telefone: new Telefone($input->telefone),
        );

        return $this->gateway->inserir($cliente);
    }
}

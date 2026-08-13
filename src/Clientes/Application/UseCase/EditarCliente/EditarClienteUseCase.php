<?php

declare(strict_types=1);

namespace App\Clientes\Application\UseCase\EditarCliente;

use App\Clientes\Application\Gateway\ClienteGatewayInterface;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\Exception\ClienteJaCadastradoException;
use App\Clientes\Domain\Exception\ClienteNaoEncontradoException;
use App\Clientes\Domain\ValueObject\Cnpj;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\CpfOrCnpjValueFactory;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;

final class EditarClienteUseCase implements EditarClienteUseCaseInterface {
    public function __construct(
        private readonly ClienteGatewayInterface $gateway,
    ) {}

    public function executar(int $id, EditarClienteInputDTO $input): Cliente {
        $cliente = $this->gateway->buscarPorId($id)
            ?? throw ClienteNaoEncontradoException::comId($id);

        if ($input->cpfCnpj !== null) {
            $novoCpfCnpj = CpfOrCnpjValueFactory::make($input->cpfCnpj);

            if ($novoCpfCnpj->getValue() !== $cliente->cpfCnpj()->getValue()) {
                $this->garantirCpfCnpjDisponivel($novoCpfCnpj);
                $cliente = $cliente->comCpfCnpj($novoCpfCnpj);
            }
        }

        if ($input->nome !== null) {
            $cliente = $cliente->comNome($input->nome);
        }

        if ($input->email !== null) {
            $cliente = $cliente->comEmail(new Email($input->email));
        }

        if ($input->telefone !== null) {
            $cliente = $cliente->comTelefone(new Telefone($input->telefone));
        }

        return $this->gateway->atualizar($cliente);
    }

    private function garantirCpfCnpjDisponivel(Cpf|Cnpj $cpfCnpj): void {
        if ($this->gateway->buscarPorCpfCnpj($cpfCnpj) !== null) {
            throw ClienteJaCadastradoException::comCpfCnpj($cpfCnpj->getFormattedValue());
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Clientes\Application\Gateway;

use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\ValueObject\Cnpj;
use App\Clientes\Domain\ValueObject\Cpf;

interface ClienteGatewayInterface {
    public function buscarPorId(int $id): ?Cliente;

    public function buscarPorCpfCnpj(Cpf|Cnpj $cpfCnpj): ?Cliente;

    public function inserir(Cliente $cliente): Cliente;

    public function atualizar(Cliente $cliente): Cliente;

    /** @return Cliente[] */
    public function listar(Cpf|Cnpj|null $cpfCnpj = null): array;
}

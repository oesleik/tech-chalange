<?php

declare(strict_types=1);

namespace App\Clientes\Infrastructure\Persistence;

use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\ValueObject\CpfOrCnpjValueFactory;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;

final class ClienteMapper {
    /** @param array<string, mixed> $linha */
    public static function paraEntidade(array $linha): Cliente {
        return Cliente::reconstituir(
            id: (int) $linha['id'],
            nome: (string) $linha['nome'],
            cpfCnpj: CpfOrCnpjValueFactory::make((string) $linha['cpf_cnpj']),
            email: new Email((string) $linha['email']),
            telefone: new Telefone((string) $linha['telefone']),
        );
    }
}

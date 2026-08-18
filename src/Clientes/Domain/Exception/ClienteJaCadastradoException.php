<?php

declare(strict_types=1);

namespace App\Clientes\Domain\Exception;

use RuntimeException;

final class ClienteJaCadastradoException extends RuntimeException {
    public static function comCpfCnpj(string $cpfCnpj): self {
        return new self("Cliente com CPF/CNPJ {$cpfCnpj} já cadastrado.");
    }
}

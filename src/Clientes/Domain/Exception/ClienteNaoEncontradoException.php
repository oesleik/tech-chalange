<?php

declare(strict_types=1);

namespace App\Clientes\Domain\Exception;

use RuntimeException;

final class ClienteNaoEncontradoException extends RuntimeException {
    public static function comId(int $id): self {
        return new self("Cliente com id {$id} não encontrado.");
    }

    public static function comCpfCnpj(string $cpfCnpj): self {
        return new self("Cliente com CPF/CNPJ {$cpfCnpj} não encontrado.");
    }
}

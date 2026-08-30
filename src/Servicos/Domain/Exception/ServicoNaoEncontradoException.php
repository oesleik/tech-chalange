<?php

declare(strict_types=1);

namespace App\Servicos\Domain\Exception;

use RuntimeException;

final class ServicoNaoEncontradoException extends RuntimeException {
    public static function comId(int $id): self {
        return new self("Serviço com id {$id} não encontrado.");
    }
}

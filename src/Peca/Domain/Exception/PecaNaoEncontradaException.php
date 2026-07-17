<?php

declare(strict_types=1);

namespace App\Peca\Domain\Exception;

use RuntimeException;

final class PecaNaoEncontradaException extends RuntimeException {
    public static function comId(int $id): self {
        return new self("Peça com id {$id} não encontrada.");
    }
}
<?php

declare(strict_types=1);

namespace App\Estoque\Domain\Exception;

final class PecaNaoEncontradaException extends \RuntimeException {
    public static function comId(int $pecaId): self {
        return new self("Peça com id {$pecaId} não encontrada.");
    }
}

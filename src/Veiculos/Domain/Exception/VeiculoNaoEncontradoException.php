<?php

declare(strict_types=1);

namespace App\Veiculos\Domain\Exception;

use RuntimeException;

final class VeiculoNaoEncontradoException extends RuntimeException {
    public static function comId(int $id): self {
        return new self("Veículo com id {$id} não encontrado.");
    }
}

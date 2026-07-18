<?php

declare(strict_types=1);

namespace App\Veiculos\Domain\Exception;

use RuntimeException;

final class VeiculoNaoEncontradoException extends RuntimeException {
    public static function comId(int $id): self {
        return new self("Veículo com id {$id} não encontrado.");
    }

    public static function comPlaca(string $placa): self {
        return new self("Veículo com placa {$placa} não encontrado.");
    }
}

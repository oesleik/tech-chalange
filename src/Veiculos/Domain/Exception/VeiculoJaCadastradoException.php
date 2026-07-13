<?php

declare(strict_types=1);

namespace App\Veiculos\Domain\Exception;

use RuntimeException;

final class VeiculoJaCadastradoException extends RuntimeException {
    public static function comPlaca(string $placa): self {
        return new self("Veículo com placa {$placa} já cadastrado.");
    }
}

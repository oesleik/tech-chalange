<?php

declare(strict_types=1);

namespace App\OrdemServico\Domain\Exception;

use RuntimeException;

final class OrdemServicoNaoEncontradaException extends RuntimeException {
    public static function comId(int $id): self {
        return new self("Ordem de serviço com id {$id} não encontrada.");
    }
}

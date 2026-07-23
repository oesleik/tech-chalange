<?php

declare(strict_types=1);

namespace App\Estoque\Domain\Exception;

final class EstoqueInsuficienteException extends \RuntimeException {
    public static function para(int $pecaId, int $disponivel, int $solicitado): self {
        return new self(
            "Estoque insuficiente para a peça {$pecaId}. Disponível: {$disponivel}, solicitado: {$solicitado}."
        );
    }
}

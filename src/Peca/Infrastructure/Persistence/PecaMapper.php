<?php

declare(strict_types=1);

namespace App\Peca\Infrastructure\Persistence;

use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\ValueObject\ValorUnitario;

final class PecaMapper {
    /** @param array<string, mixed> $linha */
    public static function paraEntidade(array $linha): Peca {
        return Peca::reconstituir(
            (int) $linha['id'],
            (string) $linha['descricao'],
            new ValorUnitario((float) $linha['valor_unitario']),
        );
    }
}
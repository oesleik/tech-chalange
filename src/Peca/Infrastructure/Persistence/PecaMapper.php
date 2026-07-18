<?php

declare(strict_types=1);

namespace App\Peca\Infrastructure\Persistence;

use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\ValueObject\ValorUnitario;

final class PecaMapper {
    public static function paraEntidade(object $row): Peca {
        return Peca::reconstituir(
            (int) $row->id,
            (string) $row->descricao,
            new ValorUnitario((float) $row->valor_unitario),
        );
    }
}
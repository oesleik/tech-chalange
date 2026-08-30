<?php

declare(strict_types=1);

namespace App\Servicos\Infrastructure\Persistence;

use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\ValueObject\ValorUnitario;

final class ServicoMapper {
    /** @param array<string, mixed> $linha */
    public static function paraEntidade(array $linha): Servico {
        return Servico::reconstituir(
            (int) $linha['id'],
            (string) $linha['descricao'],
            new ValorUnitario((float) $linha['valor_unitario']),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Veiculos\Infrastructure\Persistence;

use App\Veiculos\Domain\Entity\Veiculo;

final class VeiculoMapper {
    /**
     * @param array<string, mixed> $linha
     */
    public static function paraEntidade(array $linha): Veiculo {
        return new Veiculo(
            id: (int) $linha['id'],
            placa: $linha['placa'],
            marca: $linha['marca'],
            modelo: $linha['modelo'],
        );
    }
}

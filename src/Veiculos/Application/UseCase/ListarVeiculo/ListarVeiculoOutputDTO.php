<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\ListarVeiculo;

use App\Veiculos\Domain\Entity\Veiculo;

final class ListarVeiculoOutputDTO {
    /**
     * @param Veiculo[] $veiculos
     */
    public function __construct(
        public readonly array $veiculos,
        public readonly int $total,
        public readonly int $pagina,
        public readonly int $porPagina,
    ) {}
}

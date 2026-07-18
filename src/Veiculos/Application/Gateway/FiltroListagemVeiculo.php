<?php

declare(strict_types=1);

namespace App\Veiculos\Application\Gateway;

use App\Veiculos\Domain\Entity\Placa;

final class FiltroListagemVeiculo {
    public function __construct(
        public readonly ?Placa $placa,
        public readonly ?string $marca,
        public readonly ?string $modelo,
        public readonly int $pagina,
        public readonly int $porPagina,
    ) {}

    public function offset(): int {
        return ($this->pagina - 1) * $this->porPagina;
    }
}

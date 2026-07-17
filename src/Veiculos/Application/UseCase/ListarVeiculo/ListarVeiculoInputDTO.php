<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\ListarVeiculo;

use App\Veiculos\Domain\Entity\Placa;

final class ListarVeiculoInputDTO {
    public const int POR_PAGINA_PADRAO = 20;
    public const int POR_PAGINA_MAXIMO = 100;
    public const int PAGINA_PADRAO = 1;

    public function __construct(
        public readonly ?Placa $placa = null,
        public readonly ?string $marca = null,
        public readonly ?string $modelo = null,
        public readonly int $pagina = self::PAGINA_PADRAO,
        public readonly int $porPagina = self::POR_PAGINA_PADRAO,
    ) {}
}

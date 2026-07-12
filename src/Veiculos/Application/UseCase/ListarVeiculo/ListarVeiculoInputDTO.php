<?php

declare(strict_types=1);

namespace App\Veiculos\Application\UseCase\ListarVeiculo;

use App\Veiculos\Domain\Entity\Placa;

final class ListarVeiculoInputDTO {
    private const int POR_PAGINA_PADRAO = 20;
    private const int POR_PAGINA_MAXIMO = 100;
    private const int PAGINA_PADRAO = 1;

    public function __construct(
        public readonly ?Placa $placa = null,
        public readonly ?string $marca = null,
        public readonly ?string $modelo = null,
        public readonly int $pagina = self::PAGINA_PADRAO,
        public readonly int $porPagina = self::POR_PAGINA_PADRAO,
    ) {}

    public static function fromArray(array $data): self {
        $pagina = isset($data['pagina']) ? (int) $data['pagina'] : self::PAGINA_PADRAO;
        $porPagina = isset($data['porPagina']) ? (int) $data['porPagina'] : self::POR_PAGINA_PADRAO;

        return new self(
            placa: isset($data['placa']) ? new Placa($data['placa']) : null,
            marca: self::normalizar($data['marca'] ?? null),
            modelo: self::normalizar($data['modelo'] ?? null),
            pagina: max(1, $pagina),
            porPagina: min(self::POR_PAGINA_MAXIMO, max(1, $porPagina)),
        );
    }

    private static function normalizar(?string $valor): ?string {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        return trim($valor);
    }
}

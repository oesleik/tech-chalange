<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\DTO;

use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoInputDTO;
use App\Veiculos\Domain\Entity\Placa;

final class ListarVeiculoMapper {
    public static function fromQueryParams(array $data): ListarVeiculoInputDTO {
        $pagina = isset($data['pagina']) ? (int) $data['pagina'] : ListarVeiculoInputDTO::PAGINA_PADRAO;
        $porPagina = isset($data['porPagina']) ? (int) $data['porPagina'] : ListarVeiculoInputDTO::POR_PAGINA_PADRAO;

        return new ListarVeiculoInputDTO(
            placa: isset($data['placa']) ? new Placa($data['placa']) : null,
            marca: self::normalizar($data['marca'] ?? null),
            modelo: self::normalizar($data['modelo'] ?? null),
            pagina: max(1, $pagina),
            porPagina: min(ListarVeiculoInputDTO::POR_PAGINA_MAXIMO, max(1, $porPagina)),
        );
    }

    private static function normalizar(?string $valor): ?string {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        return trim($valor);
    }
}

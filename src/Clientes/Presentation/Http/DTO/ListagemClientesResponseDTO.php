<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\DTO;

use App\Clientes\Domain\Entity\Cliente;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class ListagemClientesResponseDTO {
    /**
     * @param ClienteResponseDTO[] $clientes
     */
    public function __construct(
        #[OA\Property(
            type: 'array',
            items: new OA\Items(ref: ClienteResponseDTO::class)
        )]
        public readonly array $clientes,
    ) {}

    /** @param Cliente[] $clientes */
    public static function fromEntities(array $clientes, bool $masked = true): self {
        return new self(
            clientes: array_map(
                static fn(Cliente $cliente) => ClienteResponseDTO::fromEntity($cliente, $masked),
                $clientes,
            ),
        );
    }
}

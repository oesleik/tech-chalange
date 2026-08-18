<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\DTO;

use App\Clientes\Domain\Entity\Cliente;
use OpenApi\Attributes as OA;

#[OA\Schema]
final class ClienteResponseDTO {
    public function __construct(
        #[OA\Property(example: 123)]
        public readonly int $id,
        #[OA\Property(example: 'Fulano de Tal')]
        public readonly string $nome,
        #[OA\Property(example: '123.456.789-09')]
        public readonly string $cpf_cnpj,
        #[OA\Property(example: 'fulano@example.com')]
        public readonly string $email,
        #[OA\Property(example: '5412345678')]
        public readonly string $telefone,
    ) {}

    public static function fromEntity(Cliente $cliente, bool $masked = false): self {
        return new self(
            id: (int) $cliente->id(),
            nome: $cliente->nome(),
            cpf_cnpj: $masked
                ? $cliente->cpfCnpj()->getMaskedValue()
                : $cliente->cpfCnpj()->getFormattedValue(),
            email: $masked ? $cliente->email()->getMaskedValue() : $cliente->email()->getValue(),
            telefone: $masked ? $cliente->telefone()->getMaskedValue() : $cliente->telefone()->getValue(),
        );
    }
}

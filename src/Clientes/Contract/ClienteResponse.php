<?php

declare(strict_types=1);

namespace App\Clientes\Contract;

use App\Clientes\Model\ClienteModel;
use App\Core\Contract\AbstractContract;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class ClienteResponse extends AbstractContract {
    public function __construct(
        #[OA\Property(example: 123)]
        public int $id,
        #[OA\Property(example: "Fulano de Tal")]
        public string $nome,
        #[OA\Property(example: "12*.***.***-89")]
        public string $cpf_cnpj,
        #[OA\Property(example: "fu********@example.com")]
        public string $email,
        #[OA\Property(example: "*********78")]
        public string $telefone,
    ) {}

    public static function fromClienteModel(ClienteModel $cliente, bool $masked = true): self {
        return new self(
            id: $cliente->getId(),
            nome: $cliente->getNome(),
            cpf_cnpj: $masked ? $cliente->getCpfCnpj()->getMaskedValue() : $cliente->getCpfCnpj()->getFormattedValue(),
            email: $masked ? $cliente->getEmail()->getMaskedValue() : $cliente->getEmail()->getValue(),
            telefone: $masked ? $cliente->getTelefone()->getMaskedValue() : $cliente->getTelefone()->getValue(),
        );
    }

}

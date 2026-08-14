<?php

declare(strict_types=1);

namespace App\OrdemServico\Contract;

use App\Core\Contract\AbstractContract;
use App\Clientes\Domain\ValueObject\CpfOrCnpjValueFactory;
use App\Veiculos\Validator\Placa;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema]
readonly class ConsultarOrdemServicoPorVeiculoEClienteRequest extends AbstractContract {
    public function __construct(
        #[OA\Property(example: '123.456.789-09')]
        public string $cpf_cnpj,
        #[OA\Property(example: 'ABC-1234')]
        public string $placa,
    ) {}

    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'cpf_cnpj' => [
                new Assert\NotBlank(message: 'O campo "cpf_cnpj" é obrigatório.'),
                new Assert\Callback(static function (mixed $value, ExecutionContextInterface $context): void {
                    if (!is_string($value)) {
                        $context->buildViolation('O campo "cpf_cnpj" deve ser uma string válida.')->addViolation();
                        return;
                    }

                    try {
                        CpfOrCnpjValueFactory::make($value);
                    } catch (InvalidArgumentException) {
                        $context->buildViolation('O campo "cpf_cnpj" é inválido.')->addViolation();
                    }
                }),
            ],
            'placa' => [
                new Assert\NotBlank(message: 'O campo "placa" é obrigatório.'),
                new Placa(),
            ],
        ]);
    }
}

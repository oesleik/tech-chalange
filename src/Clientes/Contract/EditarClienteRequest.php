<?php

declare(strict_types=1);

namespace App\Clientes\Contract;

use App\Clientes\Model\ClienteModel;
use App\Clientes\Validator\CpfOrCnpj;
use App\Clientes\ValueObject\CpfOrCnpjValueFactory;
use App\Clientes\ValueObject\EmailValue;
use App\Clientes\ValueObject\TelefoneValue;
use App\Core\Contract\AbstractContract;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema]
readonly class EditarClienteRequest extends AbstractContract {
    public function __construct(
        #[OA\Property(example: "Fulano de Tal")]
        public ?string $nome,
        #[OA\Property(example: "123.456.789-89")]
        public ?string $cpf_cnpj,
        #[OA\Property(example: "fulanodetal@example.com")]
        public ?string $email,
        #[OA\Property(example: "5412345678")]
        public ?string $telefone,
    ) {}

    public static function getConstraints(): Assert\Collection {
        return new Assert\Collection([
            'nome' => [
                new Assert\Optional(
                    new Assert\Type('string'),
                ),
            ],
            'cpf_cnpj' => [
                new Assert\Optional(
                    new CpfOrCnpj(),
                ),
            ],
            'email' => [
                new Assert\Optional(
                    new Assert\Email(),
                ),
            ],
            'telefone' => [
                new Assert\Optional(
                    new Assert\Regex(
                        pattern: '/^[0-9\(\)\s-]*$/',
                        message: 'O telefone deve conter apenas números'
                    ),
                ),
            ],
        ]);
    }

    public function toClienteModel(): ClienteModel {
        return new ClienteModel(
            id: 0,
            nome: $this->nome,
            cpfCnpj: CpfOrCnpjValueFactory::make($this->cpf_cnpj),
            email: new EmailValue($this->email),
            telefone: new TelefoneValue($this->telefone),
        );
    }

}

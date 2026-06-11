<?php

declare(strict_types=1);

namespace App\Clientes\Validator;

use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute]
class CpfOrCnpj extends Assert\Compound {
    public string $message = 'O valor informado não é um CPF ou CNPJ válido.';

    protected function getConstraints(array $options): array {
        $constraints = [new Cpf(), new Cnpj()];
        return [new Assert\AtLeastOneOf(
            constraints: $constraints,
            message: $this->message,
            includeInternalMessages: false
        )];
    }
}

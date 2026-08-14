<?php

declare(strict_types=1);

namespace App\Clientes\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Cnpj extends Constraint {
    public string $message = 'O valor informado não é um CNPJ válido.';

    public function __construct(?string $message = null, ?array $groups = null, $payload = null) {
        $this->message = $message ?? $this->message;
        parent::__construct(null, $groups, $payload);
    }
}

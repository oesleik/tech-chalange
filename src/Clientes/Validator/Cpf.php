<?php

declare(strict_types=1);

namespace App\Clientes\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Cpf extends Constraint {

    public string $message = 'The string "{{ string }}" is an invalid CPF.';

    public function __construct(?string $message = null, ?array $groups = null, $payload = null) {
        $this->message = $message ?? $this->message;
        parent::__construct(null, $groups, $payload);
    }
}

<?php

declare(strict_types=1);

namespace App\Veiculos\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Placa extends Constraint {
    public string $message = 'The string "{{ string }}" is not a valid car plate.';

    public function __construct(?string $message = null, ?array $groups = null, $payload = null) {
        $this->message = $message ?? $this->message;
        parent::__construct(null, $groups, $payload);
    }
}

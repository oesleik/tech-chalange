<?php

declare(strict_types=1);

namespace App\Clientes\Validator;

use Symfony\Component\Validator\Constraints as Assert;

#[\Attribute]
class CpfOrCnpj extends Assert\Compound {

	public string $message = 'The string "{{ string }}" is not a valid CPF or CNPJ.';

	public function __construct(?string $message = null, ?array $groups = null, $payload = null) {
		$this->message = $message ?? $this->message;
		parent::__construct(null, $groups, $payload);
	}

	protected function getConstraints(array $options): array {
		$constraints = [new Cpf(), new Cnpj()];
		return [new Assert\AtLeastOneOf($constraints, null, null, $this->message)];
	}

}

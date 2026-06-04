<?php

declare(strict_types=1);

namespace App\Core\Contract;

use Exception;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidContractException extends Exception {

	public function __construct(
		private ConstraintViolationListInterface $violations,
		string $message = "",
		int $code = 0,
		?\Throwable $previous = null
	) {
		parent::__construct($message ?: (string) $violations, $code, $previous);
	}

	public function getViolations(): ConstraintViolationListInterface {
		return $this->violations;
	}

}

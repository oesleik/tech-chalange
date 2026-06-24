<?php

declare(strict_types=1);

namespace App\Clientes\Validator;

use App\Clientes\ValueObject\CnpjValue;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CnpjValidator extends ConstraintValidator {
    public function validate(mixed $value, Constraint $constraint): void {
        if (!$constraint instanceof Cnpj) {
            throw new UnexpectedTypeException($constraint, Cnpj::class);
        }

        // NotBlank, NotNull, etc. take care of null values
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        try {
            new CnpjValue($value);
        } catch (InvalidArgumentException) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }

}

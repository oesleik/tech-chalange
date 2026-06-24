<?php

declare(strict_types=1);

namespace App\Clientes\Validator;

use App\Clientes\ValueObject\CpfValue;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class CpfValidator extends ConstraintValidator {
    public function validate(mixed $value, Constraint $constraint): void {
        if (!$constraint instanceof Cpf) {
            throw new UnexpectedTypeException($constraint, Cpf::class);
        }

        // NotBlank, NotNull, etc. take care of null values
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        try {
            new CpfValue($value);
        } catch (InvalidArgumentException) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }

}

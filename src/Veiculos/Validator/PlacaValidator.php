<?php

declare(strict_types=1);

namespace App\Veiculos\Validator;

use App\Veiculos\Domain\Entity\Placa as PlacaEntity;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PlacaValidator extends ConstraintValidator {
    public function validate(mixed $value, Constraint $constraint): void {
        if (!$constraint instanceof Placa) {
            throw new UnexpectedTypeException($constraint, Placa::class);
        }

        // NotBlank, NotNull, etc. take care of null values
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        try {
            new PlacaEntity($value);
        } catch (InvalidArgumentException) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Veiculos\Validator;

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

        if ($this->ehPlacaValida($value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ string }}', $value)
            ->addViolation();
    }

    private function ehPlacaValida(string $placa): bool {
        $placa = strtoupper(str_replace(['-', ' '], '', $placa));

        if (strlen($placa) !== 7) {
            return false;
        }

        if (preg_match("/[A-Z]{3}[0-9][A-Z0-9][0-9]{2}/", $placa)) {
            return true;
        }

        return false;
    }
}

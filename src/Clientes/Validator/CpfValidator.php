<?php

declare(strict_types=1);

namespace App\Clientes\Validator;

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

        if ($this->ehCpfValido($value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ string }}', $value)
            ->addViolation();
    }

    private function ehCpfValido(string $cpf): bool {
        $cpf = str_replace(['-', '.'], '', $cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        $base = substr($cpf, 0, 9);
        [$dv1, $dv2] = $this->descobrirDv($base);

        return $cpf === $base . $dv1 . $dv2;
    }

    private function descobrirDv(string $base): array {
        $sumDv1 = $sumDv2 = 0;

        foreach (str_split($base) as $index => $digit) {
            $digit = intval($digit);
            $sumDv1 += $digit * (10 - $index);
            $sumDv2 += $digit * (11 - $index);
        }

        $dv1 = $this->calcularDv($sumDv1);

        $sumDv2 += $dv1 * 2;
        $dv2 = $this->calcularDv($sumDv2);

        return [$dv1, $dv2];
    }

    private function calcularDv(int $sumDv): int {
        $remainder = $sumDv % 11;
        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}

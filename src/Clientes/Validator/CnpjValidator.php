<?php

declare(strict_types=1);

namespace App\Clientes\Validator;

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

        if ($this->ehCnpjValido($value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ string }}', $value)
            ->addViolation();
    }

    private function ehCnpjValido(string $cnpj): bool {
        $cnpj = strtoupper(str_replace(['-', '.', '/'], '', $cnpj));

        if (strlen($cnpj) !== 14) {
            return false;
        }

        $base = substr($cnpj, 0, 12);
        [$dv1, $dv2] = $this->descobrirDv($base);

        return $cnpj === $base . $dv1 . $dv2;
    }

    private function descobrirDv(string $base): array {
        $sumDv1 = $sumDv2 = 0;

        $pesoDv1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $pesoDv2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3];

        foreach (str_split($base) as $index => $char) {
            $digit = ord($char) - 48;
            $sumDv1 += $digit * $pesoDv1[$index];
            $sumDv2 += $digit * $pesoDv2[$index];
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

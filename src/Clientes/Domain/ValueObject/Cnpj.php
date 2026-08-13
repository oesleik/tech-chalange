<?php

declare(strict_types=1);

namespace App\Clientes\Domain\ValueObject;

use InvalidArgumentException;

final class Cnpj {
    public function __construct(
        private string $cnpj,
    ) {
        $this->cnpj = $this->limparCnpj($this->cnpj);

        if (!$this->ehCnpjValido($this->cnpj)) {
            throw new InvalidArgumentException('CNPJ inválido');
        }
    }

    public function getValue(): string {
        return $this->cnpj;
    }

    public function getFormattedValue(): string {
        return preg_replace('/(.{2})(.{3})(.{3})(.{4})(.{2})/', '$1.$2.$3/$4-$5', $this->cnpj) ?: $this->cnpj;
    }

    public function getMaskedValue(): string {
        return preg_replace('/(.{2})(.{3})(.{3})(.{4})(.{2})/', '$1.***.***/****-$5', $this->cnpj) ?: '**.***.***/****-**';
    }

    public function __toString(): string {
        return $this->getValue();
    }

    private function limparCnpj(string $cnpj): string {
        return strtoupper(str_replace(['-', '.', '/'], '', $cnpj));
    }

    private function ehCnpjValido(string $cnpj): bool {
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

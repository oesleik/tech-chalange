<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

use InvalidArgumentException;

class CpfValue {
    public function __construct(
        private string $cpf
    ) {
        $this->cpf = $this->limparCpf($this->cpf);

        if (!$this->ehCpfValido($this->cpf)) {
            throw new InvalidArgumentException("CPF inválido");
        }
    }

    public function getValue(): string {
        return $this->cpf;
    }

    public function getFormattedValue(): string {
        return preg_replace("/(.{3})(.{3})(.{3})(.{2})/", "$1.$2.$3-$4", $this->cpf) ?: $this->cpf;
    }

    public function getMaskedValue(): string {
        return preg_replace("/(.{2})(.{1})(.{3})(.{3})(.{2})/", "$1*.***.***-$5", $this->cpf) ?: "***.***.***-**";
    }

    public function __toString() {
        return $this->getValue();
    }

    private function limparCpf(string $cpf): string {
        return str_replace(['-', '.'], '', $cpf);
    }

    private function ehCpfValido(string $cpf): bool {
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

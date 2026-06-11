<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

class CnpjValue {
    public function __construct(
        private string $cnpj
    ) {
        $this->cnpj = str_replace(['-', '.', '/'], '', $cnpj);
    }

    public function getValue(): string {
        return $this->cnpj;
    }

    public function getFormattedValue(): string {
        return preg_replace("/(.{2})(.{3})(.{3})(.{4})(.{2})/", "$1.$2.$3/$4-$5", $this->cnpj) ?: $this->cnpj;
    }

    public function getMaskedValue(): string {
        if (strlen($this->cnpj) != 14) {
            return preg_replace('/./', '*', $this->cnpj);
        }

        return preg_replace("/(.{2})(.{3})(.{3})(.{4})(.{2})/", "$1.***.***/****-$5", $this->cnpj) ?: "**.***.***/****-**";
    }

    public function __toString() {
        return $this->getValue();
    }
}

<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

class EmailValue {
    public function __construct(
        private string $email
    ) {}

    public function getValue(): string {
        return $this->email;
    }

    public function getMaskedValue(): string {
        $frags = explode("@", $this->email);
        $provider = array_pop($frags);
        $username = implode("@", $frags);

        // Email inválido
        if (empty($provider)) {
            return preg_replace('/./', '*', $this->email);
        }

        // Apenas provider
        if (empty($username)) {
            return $this->email;
        }

        // Email muito pequeno, inseguro exibir algo
        if (strlen($username) < 5) {
            return preg_replace('/./', '*', $username) . "@" . $provider;
        }

        $maskedUsername = preg_replace('/./', '*', $username);
        $maskedUsername[0] = $username[0];
        $maskedUsername[1] = $username[1];
        return $maskedUsername . "@" . $provider;
    }

    public function __toString() {
        return $this->getValue();
    }
}

<?php

declare(strict_types=1);

namespace App\Clientes\ValueObject;

use InvalidArgumentException;

class EmailValue {
    public function __construct(
        private string $email
    ) {
        if (
            empty($email)
            || !preg_match("/.@./", $email)
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            throw new InvalidArgumentException("E-mail inválido");
        }
    }

    public function getValue(): string {
        return $this->email;
    }

    public function getMaskedValue(): string {
        $frags = explode("@", $this->email);
        $provider = array_pop($frags);
        $username = implode("@", $frags);

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

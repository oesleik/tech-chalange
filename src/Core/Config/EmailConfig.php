<?php

declare(strict_types=1);

namespace App\Core\Config;

class EmailConfig {
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $encryption;
    private string $fromAddress;
    private string $fromName;

    public function __construct() {
        $this->host        = getenv('MAIL_HOST') ?: 'smtp.mailtrap.io';
        $this->port         = (int) (getenv('MAIL_PORT') ?: 587);
        $this->username      = getenv('MAIL_USERNAME') ?: '';
        $this->password      = getenv('MAIL_PASSWORD') ?: '';
        $this->encryption    = getenv('MAIL_ENCRYPTION') ?: 'tls';
        $this->fromAddress  = getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@example.com';
        $this->fromName      = getenv('MAIL_FROM_NAME') ?: 'Tech Challenge';
    }

    public function getHost(): string {
        return $this->host;
    }

    public function getPort(): int {
        return $this->port;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function getEncryption(): string {
        return $this->encryption;
    }

    public function getFromAddress(): string {
        return $this->fromAddress;
    }

    public function getFromName(): string {
        return $this->fromName;
    }
}

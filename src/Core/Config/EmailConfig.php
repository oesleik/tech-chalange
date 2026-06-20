<?php

declare(strict_types=1);

namespace App\Core\Config;

class EmailConfig extends AbstractConfig {
    public function getHost(): string {
        return $this->getStringEnv('MAIL_HOST') ?: 'sandbox.smtp.mailtrap.io';
    }

    public function getPort(): int {
        return $this->getIntegerEnv('MAIL_PORT') ?: 2525;
    }

    public function getUsername(): string {
        return $this->getStringEnv('MAIL_USERNAME') ?: '';
    }

    public function getPassword(): string {
        return $this->getStringEnv('MAIL_PASSWORD') ?: '';
    }

    public function getEncryption(): string {
        return $this->getStringEnv('MAIL_ENCRYPTION') ?: 'tls';
    }

    public function getFromAddress(): string {
        return $this->getStringEnv('MAIL_FROM_ADDRESS') ?: 'no-reply@example.com';
    }

    public function getFromName(): string {
        return $this->getStringEnv('MAIL_FROM_NAME') ?: 'Tech Challenge';
    }
}

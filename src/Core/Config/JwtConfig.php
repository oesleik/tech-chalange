<?php

declare(strict_types=1);

namespace App\Core\Config;

class JwtConfig extends AbstractConfig {
    public function getSecret(): string {
        return $this->getStringEnv('JWT_SECRET')
            ?? throw MissingConfigException::make('JWT_SECRET');
    }

    public function getTtl(): int {
        return $this->getIntegerEnv('JWT_TTL') ?? 3600;
    }

    public function getIssuer(): string {
        return $this->getStringEnv('JWT_ISSUER') ?? 'tech-challenge-api';
    }
}

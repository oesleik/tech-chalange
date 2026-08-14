<?php

declare(strict_types=1);

namespace App\Core\Config;

class JwtConfig extends AbstractJwtConfig {
    protected function getSecretKey(): string {
        return 'JWT_SECRET';
    }

    protected function getTtlKey(): string {
        return 'JWT_TTL';
    }

    protected function getIssuerKey(): string {
        return 'JWT_ISSUER';
    }
}

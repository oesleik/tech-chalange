<?php

declare(strict_types=1);

namespace App\Core\Config;

class JwtOrdemServicoConfig extends AbstractJwtConfig {
    protected function getSecretKey(): string {
        return 'OS_EMAIL_ACTION_TOKEN_SECRET';
    }

    protected function getTtlKey(): string {
        return 'OS_EMAIL_ACTION_TOKEN_TTL';
    }

    protected function getIssuerKey(): string {
        return 'OS_EMAIL_ACTION_TOKEN_ISSUER';
    }
}

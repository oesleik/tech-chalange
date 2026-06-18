<?php

declare(strict_types=1);

namespace App\Core\Config;

class JwtOrdemServicoConfig extends AbstractJwtConfig {
    protected function getSecretKey(): string {
        return 'JWT_SECRET_ORDEM_SERVICO';
    }

    protected function getTtlKey(): string {
        return 'JWT_TTL_ORDEM_SERVICO';
    }

    protected function getIssuerKey(): string {
        return 'JWT_ISSUER_ORDEM_SERVICO';
    }
}

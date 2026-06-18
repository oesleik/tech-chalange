<?php

declare(strict_types=1);

namespace App\Core\Config;

abstract class AbstractJwtConfig extends AbstractConfig {

    protected abstract function getSecretKey(): string;
    protected abstract function getTtlKey(): string;
    protected abstract function getIssuerKey(): string;

    public function getSecret(): string {
        return $this->getStringEnv($this->getSecretKey())
            ?? throw MissingConfigException::make($this->getSecretKey());
    }

    public function getTtl(): int {
        return $this->getIntegerEnv($this->getTtlKey()) ?? 60 * 60 * 24 * 7;
    }

    public function getIssuer(): string {
        return $this->getStringEnv($this->getIssuerKey()) ?? 'tech-challenge-api';
    }
}
<?php

declare(strict_types=1);

namespace App\Core\Config;

class AppConfig extends AbstractConfig {
    public function getAmbiente(): AmbienteEnum {
        $appEnv = $this->getStringEnv("APP_ENV") ?: throw MissingConfigException::make("APP_ENV");
        return AmbienteEnum::tryFrom($appEnv) ?? throw InvalidConfigValueException::make("APP_ENV", "AmbienteEnum", "string");
    }

    public function getProjectRootFolder(): string {
        return realpath(__DIR__ . "/../../../") . "/";
    }

    public function getBaseUrl(): string {
        $appUrl = $this->getStringEnv("APP_URL") ?: throw MissingConfigException::make("APP_URL");
        return rtrim($appUrl, "/") . "/";
    }
}

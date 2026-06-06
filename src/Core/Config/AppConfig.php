<?php

declare(strict_types=1);

namespace App\Core\Config;

class AppConfig extends AbstractConfig {
    public function getAmbiente(): AmbienteEnum {
        $appEnv = $this->getStringEnv("APP_ENV") ?: throw MissingConfigException::make("APP_ENV");
        return AmbienteEnum::tryFrom($appEnv) ?? throw InvalidConfigValueException::make("APP_ENV", "AmbienteEnum", "string");
    }
}

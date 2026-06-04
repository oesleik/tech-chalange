<?php

declare(strict_types=1);

namespace App\Core\Config;

class DatabaseConfig extends AbstractConfig {

	public function getHost(): string {
		return $this->getStringEnv("DB_HOST") ?: throw MissingConfigException::make("DB_HOST");
	}

	public function getPort(): int {
		return $this->getIntegerEnv("DB_PORT") ?: 3306;
	}

	public function getDatabase(): string {
		return $this->getStringEnv("DB_DATABASE") ?: throw MissingConfigException::make("DB_DATABASE");
	}

	public function getUsername(): string {
		return $this->getStringEnv("DB_USERNAME") ?: throw MissingConfigException::make("DB_USERNAME");
	}

	public function getPassword(): string {
		return $this->getStringEnv("DB_PASSWORD") ?: throw MissingConfigException::make("DB_PASSWORD");
	}

}

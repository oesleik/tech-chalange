<?php

declare(strict_types=1);

namespace App\Core\Config;

/** @internal */
abstract class AbstractConfig {

	protected function getStringEnv(string $name): ?string {
		$value = $this->getEnvValue($name);

		if ($value == null) {
			return $value;
		}

		if (is_string($value)) {
			return $value;
		}

		throw InvalidConfigValueException::make($name, "string", gettype($value));
	}

	protected function getIntegerEnv(string $name): ?int {
		$value = $this->getEnvValue($name);

		if ($value == null) {
			return $value;
		}

		if (is_numeric($value) && filter_var($value, FILTER_VALIDATE_INT) !== false) {
			return intval($value);
		}

		throw InvalidConfigValueException::make($name, "int", gettype($value));
	}

	protected function getBooleanEnv(string $name): ?bool {
		$value = $this->getEnvValue($name);

		if ($value == null) {
			return $value;
		}

		if (in_array($value, [0, "0", "false", "N"], true)) {
			return false;
		}

		if (in_array($value, [1, "1", "true", "S"], true)) {
			return true;
		}

		throw InvalidConfigValueException::make($name, "boolean", gettype($value));
	}
	
	private function getEnvValue(string $name): mixed {
		if (isset($_ENV[$name])) {
			return $_ENV[$name];
		}

		$value = getenv($name);
		return $value === false ? null : $value;
	}

}

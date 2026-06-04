<?php

declare(strict_types=1);

namespace App\Core\Config;

use Error;

/** @internal */
class MissingConfigException extends Error {

	public static function make(string $name): self {
		return new self("Missing '$name' config value");
	}

}

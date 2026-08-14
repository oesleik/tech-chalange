<?php

declare(strict_types=1);

namespace App\Core\Config;

use Error;

/** @internal */
class InvalidConfigValueException extends Error {
    public static function make(string $name, string $expected, string $found): self {
        return new self("Invalid '$name' config value, expected $expected, found $found");
    }
}

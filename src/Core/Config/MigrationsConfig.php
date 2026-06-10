<?php

declare(strict_types=1);

namespace App\Core\Config;

class MigrationsConfig extends AbstractConfig {
    public function getMigrationsFolder(): string {
        return realpath(__DIR__ . "/../../migrations/") . "/";
    }
}

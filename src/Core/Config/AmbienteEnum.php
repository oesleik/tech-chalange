<?php

declare(strict_types=1);

namespace App\Core\Config;

enum AmbienteEnum: string {
    case DEV = "development";

    case TEST = "testing";

    case PROD = "production";

    public function isProd(): bool {
        return $this == self::PROD;
    }
}

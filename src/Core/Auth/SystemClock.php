<?php

declare(strict_types=1);

namespace App\Core\Auth;

use Psr\Clock\ClockInterface;

class SystemClock implements ClockInterface {
    public function now(): \DateTimeImmutable {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}

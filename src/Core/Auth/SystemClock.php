<?php

namespace App\Core\Auth;

use Psr\Clock\ClockInterface;

class SystemClock implements ClockInterface {
    public function now(): \DateTimeImmutable {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
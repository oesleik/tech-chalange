<?php

declare(strict_types=1);

namespace App\Infrastructure\NewRelic;

interface NewRelicAgentInterface {
    public function isLoaded(): bool;

    public function nameTransaction(string $name): void;

    public function addCustomParameter(string $key, string|int|float|bool $value): void;

    public function ignoreTransaction(): void;

    public function noticeError(\Throwable $error): void;

    public function recordMetric(string $name, float $value): void;
}

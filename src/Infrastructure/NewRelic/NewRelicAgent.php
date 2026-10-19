<?php

declare(strict_types=1);

namespace App\Infrastructure\NewRelic;

final class NewRelicAgent implements NewRelicAgentInterface {
    public function isLoaded(): bool {
        return extension_loaded('newrelic');
    }

    public function nameTransaction(string $name): void {
        $this->invoke('newrelic_name_transaction', $name);
    }

    public function addCustomParameter(string $key, string|int|float|bool $value): void {
        $this->invoke('newrelic_add_custom_parameter', $key, $value);
    }

    public function ignoreTransaction(): void {
        $this->invoke('newrelic_ignore_transaction');
    }

    public function noticeError(\Throwable $error): void {
        $this->invoke('newrelic_notice_error', $error);
    }

    private function invoke(string $function, mixed ...$arguments): void {
        if (!$this->isLoaded() || !function_exists($function)) {
            return;
        }

        $function(...$arguments);
    }
}

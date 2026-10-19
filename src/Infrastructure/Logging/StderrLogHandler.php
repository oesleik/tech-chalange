<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

final class StderrLogHandler {
    public static function attach(Logger $logger): void {
        $handler = new StreamHandler('php://stderr', Logger::INFO);
        $handler->setFormatter(new JsonFormatter());
        $logger->pushHandler($handler);
    }
}

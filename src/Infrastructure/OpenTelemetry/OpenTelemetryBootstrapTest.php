<?php

declare(strict_types=1);

namespace Tests\Infrastructure\OpenTelemetry;

use App\Infrastructure\OpenTelemetry\OpenTelemetryBootstrap;
use OpenTelemetry\API\Globals;
use PHPUnit\Framework\TestCase;

final class OpenTelemetryBootstrapTest extends TestCase {
    public function testInicializaOpenTelemetry(): void {
        OpenTelemetryBootstrap::initialize();

        self::assertNotNull(Globals::tracerProvider());
        self::assertNotNull(Globals::meterProvider());
        self::assertNotNull(Globals::loggerProvider());

        self::assertArrayHasKey(
            'otel_request_counter',
            $GLOBALS
        );

        self::assertArrayHasKey(
            'otel_error_counter',
            $GLOBALS
        );

        self::assertArrayHasKey(
            'otel_request_duration',
            $GLOBALS
        );
    }

    public function testNaoInicializaQuandoOpenTelemetryEstaDesabilitado(): void {
        $originalValue = getenv('OTEL_PHP_AUTOLOAD_ENABLED');

        putenv('OTEL_PHP_AUTOLOAD_ENABLED=false');

        OpenTelemetryBootstrap::initialize();

        self::assertNotNull(Globals::tracerProvider());
        self::assertNotNull(Globals::meterProvider());
        self::assertNotNull(Globals::loggerProvider());

        if ($originalValue === false) {
            putenv('OTEL_PHP_AUTOLOAD_ENABLED');
        } else {
            putenv('OTEL_PHP_AUTOLOAD_ENABLED=' . $originalValue);
        }
    }
}

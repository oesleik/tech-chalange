<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Common\Time\Clock;
use OpenTelemetry\Contrib\Otlp\LogsExporterFactory;
use OpenTelemetry\Contrib\Otlp\MetricExporterFactory;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporterFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\Processor\BatchLogRecordProcessor;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Monolog\Logger;
use OpenTelemetry\Contrib\Logs\Monolog\Handler as OpenTelemetryMonologHandler;

if (getenv('OTEL_PHP_AUTOLOAD_ENABLED') !== 'true') {
    return;
}

$resource = ResourceInfoFactory::emptyResource()->merge(
    ResourceInfo::create(
        Attributes::create([
            'service.name' => getenv('OTEL_SERVICE_NAME') ?: 'tech-challenge-api',
            'deployment.environment.name' => getenv('APP_ENV') ?: 'production',
        ])
    )
);

$transportFactory = new OtlpHttpTransportFactory();

/*
 * TRACES
 */
$tracerProvider = TracerProvider::builder()
    ->setResource($resource)
    ->addSpanProcessor(
        BatchSpanProcessor::builder(
            (new SpanExporterFactory($transportFactory))->create()
        )->build()
    )
    ->setSampler(new AlwaysOnSampler())
    ->build();

/*
 * METRICS
 */
$meterProvider = MeterProvider::builder()
    ->setResource($resource)
    ->addReader(
        new ExportingReader(
            (new MetricExporterFactory($transportFactory))->create()
        )
    )
    ->build();

/*
 * LOGS
 */
$loggerProvider = LoggerProvider::builder()
    ->setResource($resource)
    ->addLogRecordProcessor(
        new BatchLogRecordProcessor(
            (new LogsExporterFactory($transportFactory))->create(),
            Clock::getDefault()
        )
    )
    ->build();

$logger = new Logger('tech-challenge-api');

$logger->pushHandler(
    new OpenTelemetryMonologHandler(
        $loggerProvider,
        Logger::DEBUG
    )
);

$logger->info('OpenTelemetry logging funcionando', [
    'test' => 'grafana-loki',
    'environment' => getenv('APP_ENV') ?: 'production',
]);

/*
 * REGISTRA OS PROVIDERS GLOBALMENTE
 */
Sdk::builder()
    ->setTracerProvider($tracerProvider)
    ->setMeterProvider($meterProvider)
    ->setLoggerProvider($loggerProvider)
    ->setPropagator(
        \OpenTelemetry\API\Trace\Propagation\TraceContextPropagator::getInstance()
    )
    ->setAutoShutdown(true)
    ->buildAndRegisterGlobal();

/*
 * MÉTRICAS DA APLICAÇÃO
 */
$meter = Globals::meterProvider()->getMeter('tech-challenge-api');

$requestCounter = $meter->createCounter(
    'http_requests_total',
    'requests',
    'Total de requisicoes HTTP recebidas'
);

$errorCounter = $meter->createCounter(
    'http_errors_total',
    'requests',
    'Total de erros HTTP'
);

$requestDuration = $meter->createHistogram(
    'http_request_duration_seconds',
    's',
    'Duracao das requisicoes HTTP'
);

$GLOBALS['otel_request_counter'] = $requestCounter;
$GLOBALS['otel_error_counter'] = $errorCounter;
$GLOBALS['otel_request_duration'] = $requestDuration;

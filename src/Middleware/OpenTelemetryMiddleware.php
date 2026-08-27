<?php

declare(strict_types=1);

namespace App\Middleware;

use Monolog\Logger;
use OpenTelemetry\API\Globals;
use OpenTelemetry\Contrib\Logs\Monolog\Handler as OpenTelemetryMonologHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final class OpenTelemetryMiddleware implements MiddlewareInterface {
    private readonly Logger $logger;

    public function __construct() {
        $this->logger = new Logger('tech-challenge-api');

        $this->logger->pushHandler(
            new OpenTelemetryMonologHandler(
                Globals::loggerProvider(),
                Logger::DEBUG
            )
        );
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        error_log(
            'OTEL MIDDLEWARE EXECUTOU: '
            . $request->getUri()->getPath()
        );

        $start = hrtime(true);

        try {
            $response = $handler->handle($request);

            $route = $this->getRoute($request);
            $method = $request->getMethod();
            $statusCode = $response->getStatusCode();

            $this->recordRequest(
                $method,
                $route,
                $statusCode
            );

            if ($statusCode >= 400) {
                $this->recordError(
                    $method,
                    $route,
                    $statusCode
                );
            }

            return $response;
        } catch (\Throwable $exception) {
            $route = $this->getRoute($request);
            $method = $request->getMethod();

            $this->recordRequest(
                $method,
                $route,
                500
            );

            $this->recordError(
                $method,
                $route,
                500,
                $exception
            );

            throw $exception;
        } finally {
            $duration = (hrtime(true) - $start) / 1_000_000_000;

            unset($duration);
        }
    }

    private function recordRequest(
        string $method,
        string $route,
        int $statusCode
    ): void {
        error_log(
            'OTEL COUNTER: '
            . (isset($GLOBALS['otel_request_counter'])
                ? get_class($GLOBALS['otel_request_counter'])
                : 'NAO EXISTE')
        );

        $counter = $GLOBALS['otel_request_counter'] ?? null;

        if ($counter === null) {
            error_log('OTEL COUNTER NAO EXISTE - RETORNANDO');

            return;
        }

        error_log(
            "OTEL ADD: method={$method} route={$route} status={$statusCode}"
        );

        $counter->add(1, [
            'http.method' => $method,
            'http.route' => $route,
            'http.status_code' => $statusCode,
        ]);

        error_log('OTEL ADD EXECUTADO');
    }

    private function recordError(
        string $method,
        string $route,
        int $statusCode,
        ?\Throwable $exception = null
    ): void {
        $counter = $GLOBALS['otel_error_counter'] ?? null;

        if ($counter !== null) {
            $counter->add(1, [
                'http.method' => $method,
                'http.route' => $route,
                'http.status_code' => $statusCode,
            ]);
        }

        $context = [
            'http.method' => $method,
            'http.route' => $route,
            'http.status_code' => $statusCode,
        ];

        if ($exception !== null) {
            $context['exception'] = $exception;
        }

        $this->logger->error(
            "HTTP request error: {$method} {$route} ({$statusCode})",
            $context
        );
    }

    private function getRoute(ServerRequestInterface $request): string {
        try {
            $route = RouteContext::fromRequest($request)->getRoute();

            if ($route !== null) {
                return $route->getPattern();
            }
        } catch (\RuntimeException) {
            // Pode acontecer quando a requisição falha
            // antes da resolução da rota.
        }

        return $request->getUri()->getPath();
    }
}

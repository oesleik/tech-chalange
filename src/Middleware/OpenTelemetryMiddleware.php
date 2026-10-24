<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Infrastructure\Logging\StderrLogHandler;
use Monolog\Logger;
use OpenTelemetry\API\Globals;
use OpenTelemetry\Contrib\Logs\Monolog\Handler as OpenTelemetryMonologHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpException;
use Slim\Routing\RouteContext;

final class OpenTelemetryMiddleware implements MiddlewareInterface {
    private readonly Logger $logger;

    public function __construct() {
        $this->logger = new Logger('tech-challenge-api');
        StderrLogHandler::attach($this->logger);

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
                    $request,
                    $method,
                    $route,
                    $statusCode
                );
            }

            return $response;
        } catch (\Throwable $exception) {
            $route = $this->getRoute($request);
            $method = $request->getMethod();
            $statusCode = $exception instanceof HttpException ? $exception->getCode() : 500;

            $this->recordRequest(
                $method,
                $route,
                $statusCode
            );

            $this->recordError(
                $request,
                $method,
                $route,
                $statusCode,
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
        $counter = $GLOBALS['otel_request_counter'] ?? null;

        if ($counter === null) {
            return;
        }

        $counter->add(1, [
            'http.method' => $method,
            'http.route' => $route,
            'http.status_code' => $statusCode,
        ]);
    }

    private function recordError(
        ServerRequestInterface $request,
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

        $requestId = $request->getHeaderLine('X-Request-Id');
        if ($requestId !== '') {
            $context['request.id'] = $requestId;
        }

        // Atributos planos: o New Relic reconhece error.class/message/stack no log.
        if ($exception !== null) {
            $context['error.class'] = $exception::class;
            $context['error.message'] = $exception->getMessage();
            $context['error.stack'] = "{$exception->getFile()}:{$exception->getLine()}\n{$exception->getTraceAsString()}";

            if ($exception->getPrevious() !== null) {
                $context['error.previous'] = $exception->getPrevious()::class . ': ' . $exception->getPrevious()->getMessage();
            }
        }

        // 4xx é erro do cliente: fica como warning para não poluir os erros da aplicação.
        $this->logger->log(
            $statusCode >= 500 ? 'error' : 'warning',
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

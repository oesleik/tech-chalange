<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Infrastructure\NewRelic\NewRelicAgentInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final class NewRelicMiddleware implements MiddlewareInterface {
    public function __construct(
        private readonly NewRelicAgentInterface $agent,
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (!$this->agent->isLoaded()) {
            return $handler->handle($request);
        }

        $pattern = $this->routePattern($request);

        if ($this->isProbe($pattern, $request->getUri()->getPath())) {
            $this->agent->ignoreTransaction();

            return $handler->handle($request);
        }

        $this->agent->nameTransaction($request->getMethod() . ' ' . $pattern);
        $this->agent->addCustomParameter('http.method', $request->getMethod());
        $this->agent->addCustomParameter('http.route', $pattern);

        $requestId = $request->getHeaderLine('X-Request-Id');
        if ($requestId !== '') {
            $this->agent->addCustomParameter('request.id', $requestId);
        }

        $response = $handler->handle($request);
        $statusCode = $response->getStatusCode();
        $this->agent->addCustomParameter('http.status_code', $statusCode);

        if ($statusCode >= 500) {
            $this->agent->noticeError(new \RuntimeException('HTTP ' . $statusCode));
        }

        return $response;
    }

    private function routePattern(ServerRequestInterface $request): string {
        try {
            $route = RouteContext::fromRequest($request)->getRoute();
        } catch (\RuntimeException) {
            return 'unmatched';
        }

        $pattern = $route?->getPattern();

        return ($pattern === null || $pattern === '') ? 'unmatched' : $pattern;
    }

    private function isProbe(string $pattern, string $path): bool {
        return in_array($pattern, ['/health', '/healthz'], true)
            || in_array($path, ['/health', '/healthz'], true);
    }
}

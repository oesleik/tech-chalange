<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Infrastructure\NewRelic\NewRelicAgentInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Interfaces\ErrorHandlerInterface;

final class NewRelicErrorHandler implements ErrorHandlerInterface {
    public function __construct(
        private readonly NewRelicAgentInterface $agent,
        private readonly ErrorHandlerInterface $next,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        \Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
    ): ResponseInterface {
        $statusCode = $exception instanceof HttpException ? $exception->getCode() : 500;
        $path = $request->getUri()->getPath();
        $isProbe = in_array($path, ['/health', '/healthz'], true);

        if ($statusCode >= 500 && !$isProbe && $this->agent->isLoaded()) {
            $this->agent->noticeError($exception);
            $this->agent->addCustomParameter('http.status_code', $statusCode);
        }

        return ($this->next)(
            $request,
            $exception,
            $displayErrorDetails,
            $logErrors,
            $logErrorDetails,
        );
    }
}

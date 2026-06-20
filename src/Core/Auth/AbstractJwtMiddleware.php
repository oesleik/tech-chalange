<?php

declare(strict_types=1);

namespace App\Core\Auth;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractJwtMiddleware implements MiddlewareInterface {
    protected bool $useTokenQueryParam = false;

    public function __construct(
        protected readonly AbstractJwtService $jwtService,
        protected readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $token = $this->resolveToken($request);

        if ($token === null) {
            $message = 'Token não informado.';
            if ($this->useTokenQueryParam) {
                $message .= ' Use o header Authorization: Bearer <token> ou o parâmetro ?token=<token> na URL.';
            } else {
                $message .= ' Use o header Authorization: Bearer <token>.';
            }
            return $this->unauthorized($message);
        }

        try {
            $claims = $this->jwtService->validate($token);
            $request = $request->withAttribute('jwt_claims', $claims);
            return $handler->handle($request);
        } catch (JwtException $e) {
            return $this->unauthorized($e->getMessage());
        }
    }

    private function resolveToken(ServerRequestInterface $request): ?string {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        if ($this->useTokenQueryParam) {
            $queryParams = $request->getQueryParams();
            if (!empty($queryParams['token'])) {
                return $queryParams['token'];
            }
        }

        return null;
    }

    private function unauthorized(string $message): ResponseInterface {
        $response = $this->responseFactory->createResponse(401);
        $response->getBody()->write(json_encode([
            'error'   => 'Unauthorized',
            'message' => $message,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

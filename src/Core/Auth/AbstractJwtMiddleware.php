<?php

declare(strict_types=1);

namespace App\Core\Auth;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractJwtMiddleware implements MiddlewareInterface {
    public function __construct(
        protected readonly AbstractJwtService $jwtService,
        protected readonly ResponseFactoryInterface $responseFactory,
    ) {}

    abstract protected function getTokenFromRequest(ServerRequestInterface $request): ?string;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $token = $this->getTokenFromRequest($request);

        if ($token === null) {
            return $this->unauthorized("Token não informado.");
        }

        try {
            $claims = $this->jwtService->validate($token);
            $request = $request->withAttribute('jwt_claims', $claims);
            return $handler->handle($request);
        } catch (JwtException $e) {
            return $this->unauthorized($e->getMessage());
        }
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

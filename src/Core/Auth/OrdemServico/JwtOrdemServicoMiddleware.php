<?php

declare(strict_types=1);

namespace App\Core\Auth\OrdemServico;

use App\Core\Auth\JwtException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JwtOrdemServicoMiddleware implements MiddlewareInterface {
    public function __construct(
        private readonly JwtOrdemServicoService $jwtOrdemServicoService,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Token não informado. Use: Authorization: Bearer <token>');
        }

        $token = substr($authHeader, 7);

        try {
            $claims = $this->jwtOrdemServicoService->validate($token);
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

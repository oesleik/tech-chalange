<?php

declare(strict_types=1);

namespace App\Core\Auth;

use App\Core\Presentation\Http\PresenterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class JwtMiddleware extends AbstractJwtMiddleware {
    public function __construct(JwtService $jwtService, ResponseFactoryInterface $responseFactory, PresenterInterface $presenter) {
        parent::__construct($jwtService, $responseFactory, $presenter);
    }

    protected function getTokenFromRequest(ServerRequestInterface $request): ?string {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        return null;
    }
}

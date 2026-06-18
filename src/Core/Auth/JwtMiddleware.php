<?php

declare(strict_types=1);

namespace App\Core\Auth;

use Psr\Http\Message\ResponseFactoryInterface;

class JwtMiddleware extends AbstractJwtMiddleware {
    public function __construct(JwtService $jwtService, ResponseFactoryInterface $responseFactory) {
        parent::__construct($jwtService, $responseFactory);
    }
}

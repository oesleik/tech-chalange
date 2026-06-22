<?php

declare(strict_types=1);

namespace App\Core\Auth\OrdemServico;

use App\Core\Auth\AbstractJwtMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class JwtOrdemServicoMiddleware extends AbstractJwtMiddleware {
    public function __construct(JwtOrdemServicoService $jwtOrdemServicoService, ResponseFactoryInterface $responseFactory) {
        parent::__construct($jwtOrdemServicoService, $responseFactory);
    }
    
    protected function getTokenFromRequest(ServerRequestInterface $request): ?string {
        $queryParams = $request->getQueryParams();
        if (!empty($queryParams['token'])) {
            return $queryParams['token'];
        }

        return null;
    }
}

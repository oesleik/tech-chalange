<?php

declare(strict_types=1);

namespace App\Core\Auth\OrdemServico;

use App\Core\Auth\AbstractJwtMiddleware;
use App\Core\Presentation\Http\PresenterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class JwtOrdemServicoMiddleware extends AbstractJwtMiddleware {
    public function __construct(JwtOrdemServicoService $jwtOrdemServicoService, ResponseFactoryInterface $responseFactory, PresenterInterface $presenter) {
        parent::__construct($jwtOrdemServicoService, $responseFactory, $presenter);
    }

    protected function getTokenFromRequest(ServerRequestInterface $request): ?string {
        $queryParams = $request->getQueryParams();
        if (!empty($queryParams['token'])) {
            return $queryParams['token'];
        }

        return null;
    }
}

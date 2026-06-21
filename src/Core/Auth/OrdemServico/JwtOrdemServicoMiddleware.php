<?php

declare(strict_types=1);

namespace App\Core\Auth\OrdemServico;

use App\Core\Auth\AbstractJwtMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;

class JwtOrdemServicoMiddleware extends AbstractJwtMiddleware {
    protected bool $useTokenQueryParam = true;

    public function __construct(JwtOrdemServicoService $jwtOrdemServicoService, ResponseFactoryInterface $responseFactory) {
        parent::__construct($jwtOrdemServicoService, $responseFactory);
    }
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Router;

use App\OrdemServico\Presentation\Http\Controller\AtualizarSituacaoEmailController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AtualizarSituacaoEmailRouter {
    public function __construct(private readonly AtualizarSituacaoEmailController $controller) {}

    public function atualizarParaAprovada(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->controller->atualizarParaAprovada($request, $response);
    }

    public function atualizarParaRejeitada(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->controller->atualizarParaRejeitada($request, $response);
    }
}

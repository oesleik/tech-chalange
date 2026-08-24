<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Router;

use App\OrdemServico\Presentation\Http\Controller\RelatoriosOrdemServicoController;
use Psr\Http\Message\ResponseInterface;

final class RelatoriosOrdemServicoRouter {
    public function __construct(private readonly RelatoriosOrdemServicoController $controller) {}

    public function relatorioMediaTempoServicos(ResponseInterface $response): ResponseInterface {
        return $this->controller->relatorioMediaTempoServicos($response);
    }
}

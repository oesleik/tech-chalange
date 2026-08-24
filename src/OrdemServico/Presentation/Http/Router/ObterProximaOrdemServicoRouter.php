<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Router;

use App\OrdemServico\Presentation\Http\Controller\ObterProximaOrdemServicoController;
use Psr\Http\Message\ResponseInterface;

final class ObterProximaOrdemServicoRouter {
    public function __construct(private readonly ObterProximaOrdemServicoController $controller) {}

    public function __invoke(ResponseInterface $response): ResponseInterface {
        return $this->controller->execute($response);
    }
}

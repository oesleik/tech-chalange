<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Router;

use App\OrdemServico\Presentation\Http\Controller\ObterOrdemServicoController;
use Psr\Http\Message\ResponseInterface;

final class ObterOrdemServicoRouter {
    public function __construct(private readonly ObterOrdemServicoController $controller) {}

    public function __invoke(int $id, ResponseInterface $response): ResponseInterface {
        return $this->controller->execute($id, $response);
    }
}

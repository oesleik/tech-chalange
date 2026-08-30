<?php

declare(strict_types=1);

namespace App\Servicos\Presentation\Http\Router;

use App\Servicos\Presentation\Http\Controller\ListarServicosController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListarServicosRouter {
    public function __construct(private readonly ListarServicosController $controller) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->controller->execute($response);
    }
}

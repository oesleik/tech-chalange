<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\Router;

use App\Clientes\Presentation\Http\Controller\ListarClientesControllerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListarClientesRouter {
    public function __construct(
        private readonly ListarClientesControllerInterface $controller,
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->controller->execute($request, $response);
    }
}

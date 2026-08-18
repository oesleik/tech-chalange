<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\Router;

use App\Clientes\Presentation\Http\Controller\EditarClienteControllerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EditarClienteRouter {
    public function __construct(
        private readonly EditarClienteControllerInterface $controller,
    ) {}

    public function __invoke(int $id, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->controller->execute($id, $request, $response);
    }
}

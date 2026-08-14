<?php

declare(strict_types=1);

namespace App\Peca\Presentation\Http\Router;

use App\Peca\Presentation\Http\Controller\ListarPecasController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListarPecasRouter {
    public function __construct(private readonly ListarPecasController $controller) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->controller->execute($response);
    }
}

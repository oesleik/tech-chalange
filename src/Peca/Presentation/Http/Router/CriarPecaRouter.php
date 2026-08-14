<?php

declare(strict_types=1);

namespace App\Peca\Presentation\Http\Router;

use App\Peca\Presentation\Http\Controller\CriarPecaController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CriarPecaRouter {
    public function __construct(private readonly CriarPecaController $controller) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->controller->execute($request, $response);
    }
}

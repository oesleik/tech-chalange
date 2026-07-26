<?php

declare(strict_types=1);

namespace App\Estoque\Presentation\Http\Router;

use App\Estoque\Presentation\Http\Controller\ConsultarEstoquePorPecaController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Estoque\Presentation\Http\Controller\ConsultarEstoquePorPecaControllerInterface;

final class ConsultarEstoquePorPecaRouter {
    public function __construct(private readonly ConsultarEstoquePorPecaControllerInterface $controller) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface {
        // passa o $args para o controller conseguir pegar o {id} da rota
        return $this->controller->execute($request, $response, $args);
    }
}

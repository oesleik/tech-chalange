<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\Router;

use App\Veiculos\Presentation\Http\Controller\CriarVeiculoController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CriarVeiculoRouter {
    public function __construct(
        private CriarVeiculoController $controller,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        return $this->controller->execute(
            $request,
            $response,
        );
    }
}

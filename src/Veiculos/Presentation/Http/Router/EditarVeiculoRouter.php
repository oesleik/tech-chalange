<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\Router;

use App\Veiculos\Presentation\Http\Controller\EditarVeiculoController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EditarVeiculoRouter {
    public function __construct(
        private EditarVeiculoController $controller,
    ) {}

    public function __invoke(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        return $this->controller->execute(
            $id,
            $request,
            $response,
        );
    }
}

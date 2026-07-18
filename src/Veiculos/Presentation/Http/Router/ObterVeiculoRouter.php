<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\Router;

use App\Veiculos\Presentation\Http\Controller\ObterVeiculoController;
use Psr\Http\Message\ResponseInterface;

final class ObterVeiculoRouter {
    public function __construct(
        private ObterVeiculoController $controller,
    ) {}

    public function __invoke(
        int $id,
        ResponseInterface $response,
    ): ResponseInterface {
        return $this->controller->execute(
            $id,
            $response,
        );
    }
}

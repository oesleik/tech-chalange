<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\Router;

use App\Clientes\Presentation\Http\Controller\ObterClienteControllerInterface;
use Psr\Http\Message\ResponseInterface;

final class ObterClienteRouter {
    public function __construct(
        private readonly ObterClienteControllerInterface $controller,
    ) {}

    public function __invoke(int $id, ResponseInterface $response): ResponseInterface {
        return $this->controller->execute($id, $response);
    }
}

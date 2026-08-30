<?php

declare(strict_types=1);

namespace App\Servicos\Presentation\Http\Router;

use App\Servicos\Presentation\Http\Controller\CriarServicoController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CriarServicoRouter {
    public function __construct(private readonly CriarServicoController $controller) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->controller->execute($request, $response);
    }
}

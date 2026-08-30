<?php

declare(strict_types=1);

namespace App\Servicos\Presentation\Http\Router;

use App\Servicos\Presentation\Http\Controller\EditarServicoController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EditarServicoRouter {
    public function __construct(private readonly EditarServicoController $controller) {}

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $id,
    ): ResponseInterface {
        return $this->controller->execute($id, $request, $response);
    }
}

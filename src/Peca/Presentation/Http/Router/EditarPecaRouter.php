<?php

declare(strict_types=1);

namespace App\Peca\Presentation\Http\Router;

use App\Peca\Presentation\Http\Controller\EditarPecaController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EditarPecaRouter {
    public function __construct(private readonly EditarPecaController $controller) {}

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $id,
    ): ResponseInterface {
        return $this->controller->execute($id, $request, $response);
    }
}

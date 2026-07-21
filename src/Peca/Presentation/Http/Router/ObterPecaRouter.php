<?php

declare(strict_types=1);

namespace App\Peca\Presentation\Http\Router;

use App\Peca\Presentation\Http\Controller\ObterPecaController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ObterPecaRouter {
    public function __construct(private readonly ObterPecaController $controller) {}

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $id,
    ): ResponseInterface {
        return $this->controller->execute($id, $response);
    }
}

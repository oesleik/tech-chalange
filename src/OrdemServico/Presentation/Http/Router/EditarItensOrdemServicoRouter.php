<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Router;

use App\OrdemServico\Presentation\Http\Controller\EditarItensOrdemServicoController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EditarItensOrdemServicoRouter {
    public function __construct(private readonly EditarItensOrdemServicoController $controller) {}

    public function adicionarPecas(int $id, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->controller->adicionarPecas($id, $request, $response);
    }

    public function atualizarPecas(int $id, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->controller->atualizarPecas($id, $request, $response);
    }

    public function adicionarServicos(int $id, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->controller->adicionarServicos($id, $request, $response);
    }

    public function atualizarServicos(int $id, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->controller->atualizarServicos($id, $request, $response);
    }
}

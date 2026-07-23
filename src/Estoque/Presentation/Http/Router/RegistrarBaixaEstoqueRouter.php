<?php

declare(strict_types=1);

namespace App\Estoque\Presentation\Http\Router;

use App\Estoque\Presentation\Http\Controller\RegistrarBaixaEstoqueController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Estoque\Presentation\Http\Controller\RegistrarBaixaEstoqueControllerInterface;

final class RegistrarBaixaEstoqueRouter
{
    public function __construct(private readonly RegistrarBaixaEstoqueControllerInterface $controller) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        return $this->controller->execute($request, $response);
    }
}
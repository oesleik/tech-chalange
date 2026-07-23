<?php

declare(strict_types=1);

namespace App\Estoque\Presentation\Http\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RegistrarBaixaEstoqueControllerInterface
{
    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
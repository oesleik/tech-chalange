<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface EditarClienteControllerInterface {
    public function execute(int $id, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}

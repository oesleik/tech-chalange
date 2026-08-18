<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\Controller;

use Psr\Http\Message\ResponseInterface;

interface ObterClienteControllerInterface {
    public function execute(int $id, ResponseInterface $response): ResponseInterface;
}

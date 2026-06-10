<?php

declare(strict_types=1);

namespace App\Clientes;

use App\Clientes\Contract\ListarClientesResponse;
use App\Core\Contract\ContractResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Controller {
    public function listarClientes(ResponseInterface $response, ContractResolver $contractResolver, Persistence $persistence): ResponseInterface {
		$clientes = $persistence->listarClientes();
		$output = new ListarClientesResponse($clientes);
		$response->getBody()->write($contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function obterCliente(int $id, ResponseInterface $response): ResponseInterface {
        return $this->makeNotImplementedYet($response, "obterCliente $id");
    }

    public function criarCliente(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->makeNotImplementedYet($response, "criarCliente - " . json_encode($request->getParsedBody()));
    }

    public function editarCliente(int $id, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->makeNotImplementedYet($response, "editarCliente $id - " . json_encode($request->getParsedBody()));
    }

    private function makeNotImplementedYet(ResponseInterface $response, ?string $info = null): ResponseInterface {
        $response->getBody()->write("Method not implemented yet! " . $info);
        return $response;
    }

}

<?php

declare(strict_types=1);

namespace App\Clientes\Controller;

use App\Clientes\Contract\ClienteResponse;
use App\Clientes\Contract\ListarClientesResponse;
use App\Clientes\Service\ClienteService;
use App\Core\Contract\ContractResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

class ListarClientesController {
    #[OA\Get(
        path: '/clientes/',
        operationId: 'listarClientes',
        summary: 'Listar clientes cadastrados',
        tags: ['Clientes']
    )]
    #[OA\Parameter(name: 'cpfCnpj', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'CPF ou CNPJ para filtrar clientes')]
    #[OA\Response(
        response: 200,
        description: 'Lista de clientes encontrados',
        content: new OA\JsonContent(ref: '#/components/schemas/ListarClientesResponse')
    )]
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        ClienteService $service,
    ): ResponseInterface {
        $queryParams = $request->getQueryParams() ?? [];
        $cpfCnpj = $queryParams['cpfCnpj'] ?? null;

        $clientes = $service->listarClientes($cpfCnpj);

        $output = new ListarClientesResponse(
            clientes: array_map([ClienteResponse::class, "fromClienteModel"], $clientes)
        );

        $response->getBody()->write($contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }

}

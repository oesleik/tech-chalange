<?php

declare(strict_types=1);

namespace App\Veiculos\Controller;

use App\Veiculos\Contract\VeiculoResponse;
use App\Veiculos\Contract\ListarVeiculosResponse;
use App\Veiculos\Service\VeiculoService;
use App\Core\Contract\ContractResolver;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

class ListarVeiculosController {
    #[OA\Get(
        path: '/veiculos/',
        operationId: 'listarVeiculos',
        summary: 'Listar veículos cadastrados',
        tags: ['Veículos']
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista de veículos encontrados',
        content: new OA\JsonContent(ref: '#/components/schemas/ListarVeiculosResponse')
    )]
    public function __invoke(
        ResponseInterface $response,
        ContractResolver $contractResolver,
        VeiculoService $service,
    ): ResponseInterface {
        $veiculos = $service->listarVeiculos();

        $output = new ListarVeiculosResponse(
            veiculos: array_map([VeiculoResponse::class, "fromVeiculoModel"], $veiculos)
        );

        $response->getBody()->write($contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }

}

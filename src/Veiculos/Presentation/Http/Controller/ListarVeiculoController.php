<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\Controller;

use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use App\Core\Infrastructure\Presentation\HttpStatusCodeEnum;
use App\Core\Infrastructure\Presentation\PresenterInterface;
use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoInputDTO;
use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoUseCase;
use App\Veiculos\Infrastructure\Persistence\VeiculoGateway;
use App\Veiculos\Presentation\Http\DTO\ListagemVeiculosResponseDTO;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ServerRequestInterface;

final class ListarVeiculoController {
    #[OA\Get(
        path: '/veiculos/',
        operationId: 'listarVeiculos',
        summary: 'Listar veículos cadastrados',
        tags: ['Veículos']
    )]
    #[OA\Parameter(
        name: 'placa',
        in: 'query',
        required: false,
        description: 'Filtra pela placa do veículo',
        schema: new OA\Schema(type: 'string', example: 'ABC1D23')
    )]
    #[OA\Parameter(
        name: 'marca',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'Toyota')
    )]
    #[OA\Parameter(
        name: 'modelo',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'Corolla')
    )]
    #[OA\Parameter(
        name: 'pagina',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 1)
    )]
    #[OA\Parameter(
        name: 'porPagina',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista de veículos',
        content: new OA\JsonContent(
            ref: ListagemVeiculosResponseDTO::class
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Parâmetros inválidos'
    )]
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        PresenterInterface $presenter,
        DbConnectionInterface $dbConnection,
    ): ResponseInterface {
        try {
            $input = ListarVeiculoInputDTO::fromArray($request->getQueryParams());

            $veiculosGateway = new VeiculoGateway($dbConnection);
            $useCase = new ListarVeiculoUseCase($veiculosGateway);
            $resultado = $useCase->executar($input);
        } catch (InvalidArgumentException $e) {
            return $presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::BadRequest);
        }

        return $presenter->success($response, ListagemVeiculosResponseDTO::fromOutputDTO($resultado));
    }
}

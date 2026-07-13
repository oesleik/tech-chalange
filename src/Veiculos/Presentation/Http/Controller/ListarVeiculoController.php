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
    #[OA\Response(
        response: 200,
        description: 'Lista de veículos encontrados',
        content: new OA\JsonContent(ref: '#/components/schemas/ListarVeiculosResponse')
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

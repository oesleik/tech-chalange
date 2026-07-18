<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\Veiculos\Application\UseCase\ListarVeiculo\ListarVeiculoUseCase;
use App\Veiculos\Presentation\Http\DTO\ListagemVeiculosResponseDTO;
use App\Veiculos\Presentation\Http\DTO\ListarVeiculoMapper;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ServerRequestInterface;

final class ListarVeiculoController {
    public function __construct(
        private ListarVeiculoUseCase $useCase,
        private PresenterInterface $presenter,
    ) {}

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
    public function execute(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $input = ListarVeiculoMapper::fromQueryParams(
                $request->getQueryParams()
            );

            $resultado = $this->useCase->executar($input);

            return $this->presenter->success(
                $response,
                ListagemVeiculosResponseDTO::fromOutputDTO($resultado)
            );
        } catch (InvalidArgumentException $e) {
            return $this->presenter->error(
                $response,
                $e->getMessage(),
                HttpStatusCodeEnum::BadRequest
            );
        }
    }
}

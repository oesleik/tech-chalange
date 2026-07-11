<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\Controller;

use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use App\Core\Infrastructure\Presentation\HttpStatusCodeEnum;
use App\Core\Infrastructure\Presentation\PresenterInterface;
use App\Veiculos\Application\UseCase\ObterVeiculo\ObterVeiculoInput;
use App\Veiculos\Application\UseCase\ObterVeiculo\ObterVeiculoUseCase;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
use App\Veiculos\Infrastructure\Persistence\VeiculoGateway;
use App\Veiculos\Presentation\Http\DTO\VeiculoResponseDTO;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

final class ObterVeiculoController {
    #[OA\Get(
        path: '/veiculos/{id}',
        operationId: 'obterVeiculo',
        summary: 'Obter detalhes de um veículo',
        tags: ['Veículos']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Veículo encontrado',
        content: new OA\JsonContent(ref: '#/components/schemas/VeiculoResponse')
    )]
    #[OA\Response(
        response: 404,
        description: 'Veículo não encontrado'
    )]
    public function __invoke(
        int $id,
        ResponseInterface $response,
        PresenterInterface $presenter,
        DbConnectionInterface $dbConnection,
    ): ResponseInterface {
        try {
            $veiculosGateway = new VeiculoGateway($dbConnection);
            $useCase = new ObterVeiculoUseCase($veiculosGateway);
            $veiculo = $useCase->executar(new ObterVeiculoInput($id));
        } catch (VeiculoNaoEncontradoException $e) {
            return $presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::NotFound);
        }

        return $presenter->success($response, VeiculoResponseDTO::fromEntity($veiculo));
    }
}

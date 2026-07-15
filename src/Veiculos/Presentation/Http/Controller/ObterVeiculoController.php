<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\Controller;

use App\Core\Infrastructure\Presentation\HttpStatusCodeEnum;
use App\Core\Infrastructure\Presentation\PresenterInterface;
use App\Veiculos\Application\UseCase\ObterVeiculo\ObterVeiculoUseCase;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
use App\Veiculos\Presentation\Http\DTO\VeiculoResponseDTO;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

final class ObterVeiculoController {
    public function __construct(
        private ObterVeiculoUseCase $useCase,
        private PresenterInterface $presenter,
    ) {}

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
        description: 'Identificador do veículo',
        schema: new OA\Schema(
            type: 'integer',
            example: 1
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Veículo encontrado',
        content: new OA\JsonContent(ref: VeiculoResponseDTO::class)
    )]
    #[OA\Response(
        response: 404,
        description: 'Veículo não encontrado'
    )]
    public function execute(
        int $id,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $veiculo = $this->useCase->executar($id);

            return $this->presenter->success(
                $response,
                VeiculoResponseDTO::fromEntity($veiculo),
            );
        } catch (VeiculoNaoEncontradoException $e) {
            return $this->presenter->error(
                $response,
                $e->getMessage(),
                HttpStatusCodeEnum::NotFound,
            );
        } catch (InvalidArgumentException $e) {
            return $this->presenter->error(
                $response,
                $e->getMessage(),
                HttpStatusCodeEnum::BadRequest,
            );
        }
    }
}

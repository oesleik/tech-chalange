<?php

declare(strict_types=1);

namespace App\Servicos\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\Servicos\Application\UseCase\ObterServico\ObterServicoUseCase;
use App\Servicos\Domain\Exception\ServicoNaoEncontradoException;
use App\Servicos\Presentation\Http\DTO\ServicoResponseDTO;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

final class ObterServicoController {
    public function __construct(
        private ObterServicoUseCase $useCase,
        private PresenterInterface $presenter,
    ) {}

    #[OA\Get(
        path: '/servicos/{id}',
        operationId: 'obterServico',
        summary: 'Obter detalhes de um serviço',
        tags: ['Serviços']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Serviço encontrado',
        content: new OA\JsonContent(ref: ServicoResponseDTO::class)
    )]
    #[OA\Response(
        response: 404,
        description: 'Serviço não encontrado'
    )]
    public function execute(int $id, ResponseInterface $response): ResponseInterface {
        try {
            $servico = $this->useCase->executar($id);

            return $this->presenter->success(
                $response,
                ServicoResponseDTO::fromEntity($servico),
            );
        } catch (ServicoNaoEncontradoException $e) {
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

<?php

declare(strict_types=1);

namespace App\Servicos\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\Servicos\Application\UseCase\EditarServico\EditarServicoUseCase;
use App\Servicos\Domain\Exception\ServicoNaoEncontradoException;
use App\Servicos\Presentation\Http\DTO\EditarServicoMapper;
use App\Servicos\Presentation\Http\DTO\EditarServicoRequestBody;
use App\Servicos\Presentation\Http\DTO\ServicoResponseDTO;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EditarServicoController {
    public function __construct(
        private EditarServicoUseCase $useCase,
        private PresenterInterface $presenter,
    ) {}

    #[OA\Patch(
        path: '/servicos/{id}',
        operationId: 'editarServico',
        summary: 'Editar dados de um serviço',
        tags: ['Serviços']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: EditarServicoRequestBody::class)
    )]
    #[OA\Response(
        response: 200,
        description: 'Serviço atualizado',
        content: new OA\JsonContent(ref: ServicoResponseDTO::class)
    )]
    #[OA\Response(
        response: 400,
        description: 'Erro de validação'
    )]
    #[OA\Response(
        response: 404,
        description: 'Serviço não encontrado'
    )]
    public function execute(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $payload = (array) json_decode($request->getBody()->getContents(), true);
            $input = EditarServicoMapper::parse($payload);

            $servico = $this->useCase->executar($id, $input);

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

<?php

declare(strict_types=1);

namespace App\Servicos\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\Servicos\Application\UseCase\CriarServico\CriarServicoUseCase;
use App\Servicos\Presentation\Http\DTO\CriarServicoMapper;
use App\Servicos\Presentation\Http\DTO\CriarServicoRequestBody;
use App\Servicos\Presentation\Http\DTO\ServicoResponseDTO;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CriarServicoController {
    public function __construct(
        private CriarServicoUseCase $useCase,
        private PresenterInterface $presenter,
    ) {}

    #[OA\Post(
        path: '/servicos/',
        operationId: 'criarServico',
        summary: 'Cadastrar um novo serviço',
        tags: ['Serviços']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: CriarServicoRequestBody::class)
    )]
    #[OA\Response(
        response: 200,
        description: 'Serviço criado',
        content: new OA\JsonContent(ref: ServicoResponseDTO::class)
    )]
    #[OA\Response(
        response: 400,
        description: 'Erro de validação'
    )]
    public function execute(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $payload = (array) json_decode($request->getBody()->getContents(), true);
            $input = CriarServicoMapper::parse($payload);

            $servico = $this->useCase->executar($input);

            return $this->presenter->success(
                $response,
                ServicoResponseDTO::fromEntity($servico),
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

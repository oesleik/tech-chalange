<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\CriarOrdemServico\CriarOrdemServicoUseCaseInterface;
use App\OrdemServico\Presentation\Http\DTO\CriarOrdemServicoMapper;
use App\OrdemServico\Presentation\Http\DTO\OrdemServicoResumidaResponseDTO;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CriarOrdemServicoController {
    public function __construct(
        private readonly CriarOrdemServicoUseCaseInterface $useCase,
        private readonly PresenterInterface $presenter,
    ) {}

    #[OA\Post(path: '/ordens-servico/', operationId: 'criarOrdemServico', summary: 'Criar uma nova Ordem de Serviço', tags: ['Ordens de Serviço'])]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: CriarOrdemServicoMapper::class))]
    #[OA\Response(response: 201, description: 'Ordem de Serviço criada com sucesso', content: new OA\JsonContent(ref: OrdemServicoResumidaResponseDTO::class))]
    #[OA\Response(response: 400, description: 'Erro de validação')]
    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        try {
            $payload = (array) json_decode($request->getBody()->getContents(), true);
            $os = $this->useCase->executar(CriarOrdemServicoMapper::parse($payload));

            return $this->presenter->success(
                $response,
                OrdemServicoResumidaResponseDTO::fromEntity($os),
                HttpStatusCodeEnum::Created,
            );
        } catch (InvalidArgumentException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::BadRequest);
        }
    }
}

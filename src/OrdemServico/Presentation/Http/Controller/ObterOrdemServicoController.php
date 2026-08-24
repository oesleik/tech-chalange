<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoUseCaseInterface;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use App\OrdemServico\Presentation\Http\DTO\OrdemServicoCompletaResponseDTO;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

final class ObterOrdemServicoController {
    public function __construct(
        private readonly ObterOrdemServicoUseCaseInterface $useCase,
        private readonly PresenterInterface $presenter,
    ) {}

    #[OA\Get(path: '/ordens-servico/{id}', operationId: 'obterOrdemServico', summary: 'Obter detalhes de uma Ordem de Serviço', tags: ['Ordens de Serviço'])]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Detalhes da Ordem de Serviço', content: new OA\JsonContent(ref: OrdemServicoCompletaResponseDTO::class))]
    #[OA\Response(response: 404, description: 'Ordem de Serviço não encontrada')]
    public function execute(int $id, ResponseInterface $response): ResponseInterface {
        try {
            $output = $this->useCase->executar($id);

            return $this->presenter->success(
                $response,
                OrdemServicoCompletaResponseDTO::fromOutputDTO($output),
                HttpStatusCodeEnum::Ok,
            );
        } catch (OrdemServicoNaoEncontradaException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::NotFound);
        }
    }
}

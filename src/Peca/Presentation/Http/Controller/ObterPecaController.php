<?php

declare(strict_types=1);

namespace App\Peca\Presentation\Http\Controller;

use App\Peca\Application\UseCase\ObterPeca\ObterPecaUseCase;
use App\Peca\Domain\Exception\PecaNaoEncontradaException;
use App\Peca\Presentation\Http\DTO\PecaResponseDTO;
use App\Core\Contract\ContractResolver;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

final class ObterPecaController {
    public function __construct(
        private readonly ObterPecaUseCase $useCase,
        private readonly ContractResolver $contractResolver,
    ) {}

    #[OA\Get(
        path: '/pecas/{id}',
        operationId: 'obterPeca',
        summary: 'Obter detalhes de uma peça',
        tags: ['Peças']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: 'Peça encontrada',
        content: new OA\JsonContent(ref: '#/components/schemas/PecaResponseDTO')
    )]
    #[OA\Response(response: 404, description: 'Peça não encontrada')]
    public function execute(int $id, ResponseInterface $response): ResponseInterface {
        try {
            $peca = $this->useCase->executar($id);

            $output = PecaResponseDTO::fromEntity($peca);
            $response->getBody()->write($this->contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PecaNaoEncontradaException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    }
}

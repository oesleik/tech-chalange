<?php

declare(strict_types=1);

namespace App\Peca\Presentation\Http\Controller;

use App\Peca\Application\UseCase\EditarPeca\EditarPecaUseCase;
use App\Peca\Domain\Exception\PecaNaoEncontradaException;
use App\Peca\Presentation\Http\DTO\EditarPecaMapper;
use App\Peca\Presentation\Http\DTO\PecaResponseDTO;
use App\Core\Contract\ContractResolver;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

final class EditarPecaController {
    public function __construct(
        private readonly EditarPecaUseCase $useCase,
        private readonly ContractResolver $contractResolver,
    ) {}

    #[OA\Patch(
        path: '/pecas/{id}',
        operationId: 'editarPeca',
        summary: 'Editar dados de uma peça',
        tags: ['Peças']
    )]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/EditarPecaRequestBody')
    )]
    #[OA\Response(
        response: 200,
        description: 'Peça atualizada',
        content: new OA\JsonContent(ref: '#/components/schemas/PecaResponseDTO')
    )]
    #[OA\Response(response: 400, description: 'Erro de validação')]
    #[OA\Response(response: 404, description: 'Peça não encontrada')]
    public function execute(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $payload = (array) json_decode($request->getBody()->getContents(), true);
            $input = EditarPecaMapper::parse($payload);

            $peca = $this->useCase->executar($id, $input);

            $output = PecaResponseDTO::fromEntity($peca);
            $response->getBody()->write($this->contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PecaNaoEncontradaException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
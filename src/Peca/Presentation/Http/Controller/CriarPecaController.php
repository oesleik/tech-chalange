<?php

declare(strict_types=1);

namespace App\Peca\Presentation\Http\Controller;

use App\Peca\Application\UseCase\CriarPeca\CriarPecaUseCase;
use App\Peca\Presentation\Http\DTO\CriarPecaMapper;
use App\Peca\Presentation\Http\DTO\PecaResponseDTO;
use App\Core\Contract\ContractResolver;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

final class CriarPecaController {
    public function __construct(
        private readonly CriarPecaUseCase $useCase,
        private readonly ContractResolver $contractResolver,
    ) {}

    #[OA\Post(
        path: '/pecas/',
        operationId: 'criarPeca',
        summary: 'Cadastrar uma nova peça',
        tags: ['Peças']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/CriarPecaRequestBody')
    )]
    #[OA\Response(
        response: 200,
        description: 'Peça criada',
        content: new OA\JsonContent(ref: '#/components/schemas/PecaResponseDTO')
    )]
    #[OA\Response(response: 400, description: 'Erro de validação')]
    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        try {
            $payload = (array) json_decode($request->getBody()->getContents(), true);
            $input = CriarPecaMapper::parse($payload);

            $peca = $this->useCase->executar($input);

            $output = PecaResponseDTO::fromEntity($peca);
            $response->getBody()->write($this->contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}

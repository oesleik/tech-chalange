<?php

declare(strict_types=1);

namespace App\Peca\Presentation\Http\Controller;

use App\Peca\Application\UseCase\ListarPeca\ListarPecaUseCase;
use App\Peca\Presentation\Http\DTO\ListarPecasResponseDTO;
use App\Peca\Presentation\Http\DTO\PecaResponseDTO;
use App\Core\Contract\ContractResolver;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

final class ListarPecasController {
    public function __construct(
        private readonly ListarPecaUseCase $useCase,
        private readonly ContractResolver $contractResolver,
    ) {}

    #[OA\Get(
        path: '/pecas/',
        operationId: 'listarPecas',
        summary: 'Listar peças cadastradas',
        tags: ['Peças']
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista de peças encontradas',
        content: new OA\JsonContent(ref: '#/components/schemas/ListarPecasResponseDTO')
    )]
    public function execute(ResponseInterface $response): ResponseInterface {
        $pecas = $this->useCase->executar();

        $output = new ListarPecasResponseDTO(
            pecas: array_map(PecaResponseDTO::fromEntity(...), $pecas),
        );

        $response->getBody()->write($this->contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

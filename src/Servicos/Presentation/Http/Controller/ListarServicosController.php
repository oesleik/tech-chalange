<?php

declare(strict_types=1);

namespace App\Servicos\Presentation\Http\Controller;

use App\Core\Presentation\Http\PresenterInterface;
use App\Servicos\Application\UseCase\ListarServicos\ListarServicosUseCase;
use App\Servicos\Presentation\Http\DTO\ListarServicosResponseDTO;
use App\Servicos\Presentation\Http\DTO\ServicoResponseDTO;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

final class ListarServicosController {
    public function __construct(
        private ListarServicosUseCase $useCase,
        private PresenterInterface $presenter,
    ) {}

    #[OA\Get(
        path: '/servicos/',
        operationId: 'listarServicos',
        summary: 'Listar serviços cadastrados',
        tags: ['Serviços']
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista de serviços encontrados',
        content: new OA\JsonContent(ref: ListarServicosResponseDTO::class)
    )]
    public function execute(ResponseInterface $response): ResponseInterface {
        $servicos = $this->useCase->executar();

        $output = new ListarServicosResponseDTO(
            servicos: array_map(ServicoResponseDTO::fromEntity(...), $servicos),
        );

        return $this->presenter->success($response, $output);
    }
}

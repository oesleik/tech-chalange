<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\ListarOrdensServico\ListarOrdensServicoUseCaseInterface;
use App\OrdemServico\Presentation\Http\DTO\ListarOrdensServicoMapper;
use App\OrdemServico\Presentation\Http\DTO\OrdemServicoResumidaResponseDTO;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListarOrdensServicoController {
    public function __construct(
        private readonly ListarOrdensServicoUseCaseInterface $useCase,
        private readonly PresenterInterface $presenter,
    ) {}

    #[OA\Get(path: '/ordens-servico/', operationId: 'listarOrdensServico', summary: 'Listar todas as Ordens de Serviço', tags: ['Ordens de Serviço'])]
    #[OA\Parameter(name: 'situacao', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id_cliente', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'id_veiculo', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Lista de Ordens de Serviço')]
    #[OA\Response(response: 400, description: 'Filtros inválidos')]
    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        try {
            $lista = $this->useCase->executar(
                ListarOrdensServicoMapper::fromQueryParams($request->getQueryParams()),
            );

            $dto = (object) ['ordens_servico' => array_map(OrdemServicoResumidaResponseDTO::fromEntity(...), $lista)];

            return $this->presenter->success($response, $dto, HttpStatusCodeEnum::Ok);
        } catch (InvalidArgumentException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::BadRequest);
        }
    }
}

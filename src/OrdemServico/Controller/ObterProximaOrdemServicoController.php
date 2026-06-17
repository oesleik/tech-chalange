<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\Core\Config\AppConfig;
use App\OrdemServico\Service\OrdemServicoService;
use App\Core\Contract\ContractResolver;
use App\OrdemServico\Contract\ProximaOrdemServicoResponse;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

class ObterProximaOrdemServicoController {
    #[OA\Get(
        path: '/ordens-servico/proxima',
        operationId: 'obterProximaOrdemServico',
        summary: 'Obter próxima ordem de serviço na fila (aguardando diagnóstico ou aprovada)',
        tags: ['Ordens de Serviço']
    )]
    #[OA\Response(
        response: 200,
        description: 'Detalhes da Ordem de Serviço',
        content: new OA\JsonContent(ref: '#/components/schemas/ProximaOrdemServicoResponse')
    )]
    #[OA\Response(
        response: 204,
        description: 'Nenhuma Ordem de Serviço pendente ou pronta para trabalhar'
    )]
    public function __invoke(
        ResponseInterface $response,
        ContractResolver $contractResolver,
        OrdemServicoService $service,
        AppConfig $appConfig,
    ): ResponseInterface {
        $ordemServico = $service->obterProximaOrdemServicoNaFila();

        if (!$ordemServico) {
            return $response->withStatus(204);
        }

        $output = ProximaOrdemServicoResponse::fromModel($ordemServico, $appConfig);
        $response->getBody()->write($contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

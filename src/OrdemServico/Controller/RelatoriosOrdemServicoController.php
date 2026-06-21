<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\Core\Contract\ContractResolver;
use App\OrdemServico\Service\RelatorioMediaTempoServicosService;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;

class RelatoriosOrdemServicoController {
    #[OA\Get(
        path: '/ordens-servico/relatorios/media_tempo_servicos',
        operationId: 'relatorioMediaTempoServicos',
        summary: 'Relatório sobre média de tempo por serviço na execução de ordens de serviço',
        tags: ['Ordens de Serviço - Relatórios']
    )]
    #[OA\Response(
        response: 200,
        description: 'Relatório sobre a média de tempo de execução por serviço',
        content: new OA\JsonContent(ref: '#/components/schemas/RelatorioMediaTempoServicosResponse')
    )]
    public function relatorioMediaTempoServicos(
        RelatorioMediaTempoServicosService $service,
        ContractResolver $contractResolver,
        ResponseInterface $response,
    ): ResponseInterface {
        $relatorio = $service->gerarRelatorio();
        $response->getBody()->write($contractResolver->toJson($relatorio));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\GerarRelatorioMediaTempo\GerarRelatorioMediaTempoUseCaseInterface;
use App\OrdemServico\Presentation\Http\DTO\ServicoRelatorioMediaTempoResponseDTO;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

final class RelatoriosOrdemServicoController {
    public function __construct(
        private readonly GerarRelatorioMediaTempoUseCaseInterface $useCase,
        private readonly PresenterInterface $presenter,
    ) {}

    #[OA\Get(path: '/ordens-servico/relatorios/media_tempo_servicos', operationId: 'relatorioMediaTempoServicos', summary: 'Relatório sobre média de tempo por serviço', tags: ['Ordens de Serviço - Relatórios'])]
    #[OA\Response(response: 200, description: 'Relatório gerado')]
    public function relatorioMediaTempoServicos(ResponseInterface $response): ResponseInterface {
        $servicos = $this->useCase->executar();
        $dto      = (object) [
            'servicos' => array_map(ServicoRelatorioMediaTempoResponseDTO::fromDTO(...), $servicos),
        ];

        return $this->presenter->success($response, $dto, HttpStatusCodeEnum::Ok);
    }
}

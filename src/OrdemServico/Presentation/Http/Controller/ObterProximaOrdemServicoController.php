<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Controller;

use App\Core\Config\AppConfig;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\ObterProximaOrdemServico\ObterProximaOrdemServicoUseCaseInterface;
use App\OrdemServico\Presentation\Http\DTO\ProximaOrdemServicoResponseDTO;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

final class ObterProximaOrdemServicoController {
    public function __construct(
        private readonly ObterProximaOrdemServicoUseCaseInterface $useCase,
        private readonly PresenterInterface $presenter,
        private readonly AppConfig $appConfig,
    ) {}

    #[OA\Get(path: '/ordens-servico/proxima', operationId: 'obterProximaOrdemServico', summary: 'Obter próxima ordem de serviço na fila', tags: ['Ordens de Serviço'])]
    #[OA\Response(response: 200, description: 'Próxima Ordem de Serviço', content: new OA\JsonContent(ref: ProximaOrdemServicoResponseDTO::class))]
    #[OA\Response(response: 204, description: 'Nenhuma Ordem de Serviço pendente')]
    public function execute(ResponseInterface $response): ResponseInterface {
        $output = $this->useCase->executar();

        if ($output === null) {
            return $response->withStatus(204);
        }

        return $this->presenter->success(
            $response,
            ProximaOrdemServicoResponseDTO::fromOutputDTO($output, $this->appConfig),
            HttpStatusCodeEnum::Ok,
        );
    }
}

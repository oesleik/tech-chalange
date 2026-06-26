<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\OrdemServico\Service\EnviarOrcamentoOrdemServicoEmailService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/ordens-servico/{id}/enviar-orcamento',
    operationId: 'enviarOrcamentoEmail',
    summary: 'Envia o orçamento da Ordem de Serviço por e-mail ao cliente',
    tags: ['Ordens de Serviço']
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
#[OA\Response(response: 200, description: 'E-mail enviado com sucesso')]
#[OA\Response(response: 404, description: 'Ordem de Serviço não encontrada')]
#[OA\Response(response: 500, description: 'Falha ao enviar o e-mail')]
class EnviarOrcamentoOrdemServicoEmailController {
    public function __construct(
        private EnviarOrcamentoOrdemServicoEmailService $service,
    ) {}

    public function __invoke(
        int $id,
        ResponseInterface $response,
    ): ResponseInterface {
        if ($id <= 0) {
            $response->getBody()->write(json_encode(['erro' => 'ID da Ordem de Serviço inválido.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $this->service->enviar($id);

            $response->getBody()->write(json_encode([
                'mensagem' => "Orçamento da Ordem de Serviço #{$id} enviado com sucesso.",
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['erro' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    }
}

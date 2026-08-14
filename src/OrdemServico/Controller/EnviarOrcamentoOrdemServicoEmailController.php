<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\Clientes\Service\ClienteService;
use App\OrdemServico\Service\EnviarOrcamentoOrdemServicoEmailService;
use App\OrdemServico\Service\OrdemServicoService;
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
        private OrdemServicoService $ordemServicoService,
        private ClienteService $clienteService,
    ) {}

    public function __invoke(
        int $id,
        ResponseInterface $response,
    ): ResponseInterface {
        $ordemServico = $this->ordemServicoService->obterOrdemServicoPorId($id);

        if ($ordemServico === null) {
            return $response->withStatus(404, "Ordem de serviço não encontrada");
        }

        $cliente = $this->clienteService->obterClientePorId($ordemServico->getIdCliente());

        if ($cliente === null) {
            return $response->withStatus(404, "Cliente não encontrado");
        }

        $this->service->enviar($ordemServico, $cliente);

        $response->getBody()->write(json_encode([
            'mensagem' => "Orçamento da Ordem de Serviço #{$id} enviado com sucesso.",
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}

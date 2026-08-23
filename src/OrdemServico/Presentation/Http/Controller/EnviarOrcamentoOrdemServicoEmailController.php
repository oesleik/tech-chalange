<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Controller;

use App\Clientes\Application\UseCase\ObterCliente\ObterClienteUseCaseInterface;
use App\Clientes\Domain\Exception\ClienteNaoEncontradoException;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use App\OrdemServico\Service\EnviarOrcamentoOrdemServicoEmailService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(path: '/ordens-servico/{id}/enviar-orcamento', operationId: 'enviarOrcamentoEmail', summary: 'Envia o orçamento da OS por e-mail ao cliente', tags: ['Ordens de Serviço'])]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
#[OA\Response(response: 200, description: 'E-mail enviado com sucesso')]
#[OA\Response(response: 404, description: 'OS ou cliente não encontrados')]
final class EnviarOrcamentoOrdemServicoEmailController {
    public function __construct(
        private readonly EnviarOrcamentoOrdemServicoEmailService $emailService,
        private readonly OrdemServicoGatewayInterface $ordemServicoGateway,
        private readonly ObterClienteUseCaseInterface $clienteUseCase,
        private readonly PresenterInterface $presenter,
    ) {}

    public function __invoke(int $id, ResponseInterface $response): ResponseInterface {
        $os = $this->ordemServicoGateway->buscarPorId($id);

        if ($os === null) {
            return $this->presenter->error($response, 'Ordem de serviço não encontrada.', HttpStatusCodeEnum::NotFound);
        }

        try {
            $cliente = $this->clienteUseCase->executar($os->idCliente());
        } catch (ClienteNaoEncontradoException) {
            return $this->presenter->error($response, 'Cliente não encontrado.', HttpStatusCodeEnum::NotFound);
        }

        $this->emailService->enviar($os, $cliente);

        return $this->presenter->success(
            $response,
            (object) ['mensagem' => "Orçamento da Ordem de Serviço #{$id} enviado com sucesso."],
            HttpStatusCodeEnum::Ok,
        );
    }
}

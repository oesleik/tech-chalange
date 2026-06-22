<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\Core\Database\DatabaseErrorEnum;
use App\Core\Contract\AbstractContract;
use App\OrdemServico\Contract\OrdemServicoCompletaResponse;
use App\OrdemServico\Service\OrdemServicoService;
use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Core\Contract\ValidationError;
use App\Core\Contract\ValidationErrorResponse;
use App\OrdemServico\Contract\PecasOrdemServicoRequest;
use App\OrdemServico\Contract\ServicosOrdemServicoRequest;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Service\ItensOrdemServicoService;
use Closure;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;
use PDOException;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Post(
    path: '/ordens-servico/{id}/pecas',
    operationId: 'adicionarPecasOrdemServico',
    summary: 'Adicionar peças na ordem de serviço',
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PecasOrdemServicoRequest')),
    tags: ['Ordens de Serviço - Peças e Serviços']
)]
#[OA\Put(
    path: '/ordens-servico/{id}/pecas',
    operationId: 'atualizarPecasOrdemServico',
    summary: 'Atualizar peças da ordem de serviço (substitui todas as peças atuais)',
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/PecasOrdemServicoRequest')),
    tags: ['Ordens de Serviço - Peças e Serviços']
)]
#[OA\Post(
    path: '/ordens-servico/{id}/servicos',
    operationId: 'adicionarServicosOrdemServico',
    summary: 'Adicionar serviços na ordem de serviço',
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ServicosOrdemServicoRequest')),
    tags: ['Ordens de Serviço - Peças e Serviços']
)]
#[OA\Put(
    path: '/ordens-servico/{id}/servicos',
    operationId: 'atualizarServicosOrdemServico',
    summary: 'Atualizar serviços da ordem de serviço (substitui todos os serviços atuais)',
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/ServicosOrdemServicoRequest')),
    tags: ['Ordens de Serviço - Peças e Serviços']
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'integer')
)]
#[OA\Response(
    response: 200,
    description: 'Situação atualizada com sucesso',
    content: new OA\JsonContent(ref: '#/components/schemas/OrdemServicoCompletaResponse')
)]
#[OA\Response(
    response: 400,
    description: 'Validation error',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
)]
#[OA\Response(
    response: 404,
    description: 'Ordem de serviço não encontrada'
)]
#[OA\Response(
    response: 422,
    description: 'Não foi possível editar os itens da ordem de serviço'
)]
class EditarItensOrdemServicoController {
    public function __construct(
        private ContractResolver $contractResolver,
        private OrdemServicoService $ordemServicoService,
        private ItensOrdemServicoService $itensService,
    ) {}

    public function adicionarPecas(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $handler = function (OrdemServicoModel $ordemServico, PecasOrdemServicoRequest $req): void {
            $pecas = $req->toPecasOrdemServicoModelArray();
            $this->itensService->adicionarPecas($ordemServico, $pecas);
        };

        return $this->editarItens($id, $request, $response, PecasOrdemServicoRequest::class, "id_peca", $handler);
    }

    public function atualizarPecas(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $handler = function (OrdemServicoModel $ordemServico, PecasOrdemServicoRequest $req): void {
            $pecas = $req->toPecasOrdemServicoModelArray();
            $this->itensService->atualizarPecas($ordemServico, $pecas);
        };

        return $this->editarItens($id, $request, $response, PecasOrdemServicoRequest::class, "id_peca", $handler);
    }

    public function adicionarServicos(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $handler = function (OrdemServicoModel $ordemServico, ServicosOrdemServicoRequest $req): void {
            $servicos = $req->toServicosOrdemServicoModelArray();
            $this->itensService->adicionarServicos($ordemServico, $servicos);
        };

        return $this->editarItens($id, $request, $response, ServicosOrdemServicoRequest::class, "id_servico", $handler);
    }

    public function atualizarServicos(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $handler = function (OrdemServicoModel $ordemServico, ServicosOrdemServicoRequest $req): void {
            $servicos = $req->toServicosOrdemServicoModelArray();
            $this->itensService->atualizarServicos($ordemServico, $servicos);
        };

        return $this->editarItens($id, $request, $response, ServicosOrdemServicoRequest::class, "id_servico", $handler);
    }

    /**
     * @template T of AbstractContract
     * @param class-string<T> $contractClass
     * @param Closure(OrdemServicoModel $ordemServico, T $contract):void $handler
     */
    private function editarItens(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $contractClass,
        string $idField,
        Closure $handler,
    ): ResponseInterface {
        try {
            $payload = json_decode($request->getBody()->getContents(), true);
            $contract = $this->contractResolver->fromArray($payload, $contractClass);

            $ordemServico = $this->ordemServicoService->obterOrdemServicoPorId($id);

            if (!$ordemServico) {
                return $response->withStatus(404, "Ordem de serviço não encontrada");
            }

            if ($ordemServico->getSituacao()->estaFinalizada()) {
                return $response->withStatus(422, "A ordem de serviço já está finalizada");
            }

            $handler($ordemServico, $contract);

			// Obtendo dados atualizados
			$ordemServico = $this->ordemServicoService->obterOrdemServicoPorId($id);
            $pecas = $this->itensService->obterPecasPorIdOrdemServico($ordemServico->getId());
            $servicos = $this->itensService->obterServicosPorIdOrdemServico($ordemServico->getId());

            $output = OrdemServicoCompletaResponse::fromModel($ordemServico, $pecas, $servicos);
            $response->getBody()->write($this->contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidContractException $e) {
            $response->getBody()->write($this->contractResolver->toJson(ValidationErrorResponse::from($e->getViolations())));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            if (DatabaseErrorEnum::fromPdoException($e) == DatabaseErrorEnum::NO_REFERENCED_ROW) {
                $response->getBody()->write($this->contractResolver->toJson(new ValidationErrorResponse([
                    new ValidationError($idField, "Item não encontrado no sistema."),
                ])));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }
            throw $e;
        }
    }
}

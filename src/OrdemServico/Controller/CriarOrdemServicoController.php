<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\OrdemServico\Contract\CriarOrdemServicoRequest;
use App\OrdemServico\Contract\OrdemServicoResponse;
use App\OrdemServico\Service\OrdemServicoService;
use App\Core\Contract\ContractResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

class CriarOrdemServicoController
{
    #[OA\Post(
        path: '/ordens-servico',
        operationId: 'criarOrdemServico',
        summary: 'Criar uma nova Ordem de Serviço',
        tags: ['Ordens de Serviço']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/CriarOrdemServicoRequest'
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Ordem de Serviço criada com sucesso',
        content: new OA\JsonContent(ref: '#/components/schemas/OrdemServicoResponse')
    )]
    #[OA\Response(
        response: 400,
        description: 'Erro de validação',
        content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
    )]
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        OrdemServicoService $service,
    ): ResponseInterface {
        try {
            $payload = json_decode($request->getBody()->getContents(), true);
            $req = $contractResolver->fromArray($payload, CriarOrdemServicoRequest::class);

            $ordemServico = $req->toOrdemServicoModel();
            $ordemServicoCriada = $service->criarOrdemServico($ordemServico);

            $output = OrdemServicoResponse::fromModel($ordemServicoCriada);

            $response->getBody()->write($contractResolver->toJson($output));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['erro' => $e->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }
}

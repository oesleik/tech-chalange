<?php

declare(strict_types=1);

namespace App\Servicos\Controller;

use App\Servicos\Contract\ServicoResponse;
use App\Servicos\Contract\CriarServicoRequest;
use App\Servicos\Service\ServicosService;
use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Core\Contract\ValidationErrorResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

class CriarServicoController {
    #[OA\Post(
        path: '/servicos/',
        operationId: 'criarServico',
        summary: 'Cadastrar um novo serviço',
        tags: ['Serviços']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/CriarServicoRequest'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Serviço criado',
        content: new OA\JsonContent(ref: '#/components/schemas/ServicoResponse')
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error',
        content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
    )]
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        ServicosService $service,
    ): ResponseInterface {
        try {
            $payload = json_decode($request->getBody()->getContents(), true);
            $req = $contractResolver->fromArray($payload, CriarServicoRequest::class);

            $servico = $req->toServicoModel();
            $servicoCriada = $service->criarServico($servico);

            $output = ServicoResponse::fromServicoModel($servicoCriada);
            $response->getBody()->write($contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidContractException $e) {
            $response->getBody()->write($contractResolver->toJson(ValidationErrorResponse::from($e->getViolations())));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}

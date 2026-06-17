<?php

declare(strict_types=1);

namespace App\Peca\Controller;

use App\Peca\Contract\PecaResponse;
use App\Peca\Contract\CriarPecaRequest;
use App\Peca\Service\PecaService;
use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Core\Contract\ValidationErrorResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

class CriarPecaController {
    #[OA\Post(
        path: '/pecas/',
        operationId: 'criarPeca',
        summary: 'Cadastrar uma nova peça',
        tags: ['Peças']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/CriarPecaRequest'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Peça criada',
        content: new OA\JsonContent(ref: '#/components/schemas/PecaResponse')
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
        PecaService $service,
    ): ResponseInterface {
        try {
            $payload = json_decode($request->getBody()->getContents(), true);
            $req = $contractResolver->fromArray($payload, CriarPecaRequest::class);

            $peca = $req->toPecaModel();
            $pecaCriada = $service->criarPeca($peca);

            $output = PecaResponse::fromPecaModel($pecaCriada);
            $response->getBody()->write($contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidContractException $e) {
            $response->getBody()->write($contractResolver->toJson(ValidationErrorResponse::from($e->getViolations())));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}

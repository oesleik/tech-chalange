<?php

declare(strict_types=1);

namespace App\Peca\Controller;

use App\Peca\Contract\PecaResponse;
use App\Peca\Contract\EditarPecaRequest;
use App\Peca\Model\PecaModel;
use App\Peca\Service\PecaService;
use App\Peca\ValueObject\DescricaoValue;
use App\Peca\ValueObject\ValorUnitarioValue;
use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Core\Contract\ValidationErrorResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

class EditarPecaController {
    #[OA\Patch(
        path: '/pecas/{id}',
        operationId: 'editarPeca',
        summary: 'Editar dados de uma peça',
        tags: ['Peças']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/EditarPecaRequest'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Peça atualizada',
        content: new OA\JsonContent(ref: '#/components/schemas/PecaResponse')
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error',
        content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
    )]
    #[OA\Response(
        response: 404,
        description: 'Peça não encontrada'
    )]
    public function __invoke(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        PecaService $service,
    ): ResponseInterface {
        try {
            $payload = json_decode($request->getBody()->getContents(), true);
            $req = $contractResolver->fromArray($payload, EditarPecaRequest::class);

            $peca = $service->obterPecaPorId($id);

            if (empty($peca)) {
                return $response->withStatus(404, "Peça não encontrada");
            }

            $pecaAtualizar = new PecaModel(
                id: $peca->getId(),
                descricao: new DescricaoValue($req->descricao ?? $peca->getDescricao()->getValue()),
                valorUnitario: new ValorUnitarioValue($req->valor_unitario ?? $peca->getValorUnitario()->getValue()),
            );

            $service->atualizarPeca($pecaAtualizar);
            $output = PecaResponse::fromPecaModel($pecaAtualizar);

            $response->getBody()->write($contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidContractException $e) {
            $response->getBody()->write($contractResolver->toJson(ValidationErrorResponse::from($e->getViolations())));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
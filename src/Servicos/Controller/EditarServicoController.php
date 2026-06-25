<?php

declare(strict_types=1);

namespace App\Servicos\Controller;

use App\Servicos\Contract\ServicoResponse;
use App\Servicos\Contract\EditarServicoRequest;
use App\Servicos\Model\ServicoModel;
use App\Servicos\Service\ServicosService;
use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Core\Contract\ValidationErrorResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

class EditarServicoController {
    #[OA\Patch(
        path: '/servicos/{id}',
        operationId: 'editarServico',
        summary: 'Editar dados de um serviço',
        tags: ['Serviços']
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
            ref: '#/components/schemas/EditarServicoRequest'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Serviço atualizado',
        content: new OA\JsonContent(ref: '#/components/schemas/ServicoResponse')
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error',
        content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
    )]
    #[OA\Response(
        response: 404,
        description: 'Serviço não encontrado'
    )]
    public function __invoke(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        ServicosService $service,
    ): ResponseInterface {
        try {
            $payload = json_decode($request->getBody()->getContents(), true);
            $req = $contractResolver->fromArray($payload, EditarServicoRequest::class);

            $servico = $service->obterServicoPorId($id);

            if (empty($servico)) {
                return $response->withStatus(404, "Serviço não encontrado");
            }

            $servicoAtualizar = new ServicoModel(
                id: $servico->getId(),
                descricao: $req->descricao ?? $servico->getDescricao(),
                valorUnitario: floatval($req->valor_unitario ?? $servico->getValorUnitario()),
            );

            $service->atualizarServico($servicoAtualizar);
            $output = ServicoResponse::fromServicoModel($servicoAtualizar);

            $response->getBody()->write($contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidContractException $e) {
            $response->getBody()->write($contractResolver->toJson(ValidationErrorResponse::from($e->getViolations())));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}

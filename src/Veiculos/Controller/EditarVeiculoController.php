<?php

declare(strict_types=1);

namespace App\Veiculos\Controller;

use App\Veiculos\Contract\VeiculoResponse;
use App\Veiculos\Contract\EditarVeiculoRequest;
use App\Veiculos\Model\VeiculoModel;
use App\Veiculos\Service\VeiculoService;
use App\Core\Consts\SqlStateEnum;
use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Core\Contract\ValidationError;
use App\Core\Contract\ValidationErrorResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;
use PDOException;

class EditarVeiculoController {
    #[OA\Patch(
        path: '/veiculos/{id}',
        operationId: 'editarVeiculo',
        summary: 'Editar dados de um veículo',
        tags: ['Veículos']
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
            ref: '#/components/schemas/EditarVeiculoRequest'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Veículo atualizado',
        content: new OA\JsonContent(ref: '#/components/schemas/VeiculoResponse')
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error',
        content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
    )]
    #[OA\Response(
        response: 404,
        description: 'Veículo não encontrado'
    )]
    #[OA\Response(
        response: 409,
        description: 'Conflict error',
        content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
    )]
    public function __invoke(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        VeiculoService $service,
    ): ResponseInterface {
        try {
            $payload = json_decode($request->getBody()->getContents(), true);
            $req = $contractResolver->fromArray($payload, EditarVeiculoRequest::class);
            $veiculo = $service->obterVeiculoPorId($id);
            if (empty($veiculo)) {
                return $response->withStatus(404, "Veículo não encontrado");
            }
            $veiculoAtualizar = new VeiculoModel(
                id: $veiculo->getId(),
                placa: $req->placa ?? $veiculo->getPlaca(),
                marca: $req->marca ?? $veiculo->getMarca(),
                modelo: $req->modelo ?? $veiculo->getModelo(),
            );
            $service->atualizarVeiculo($veiculoAtualizar);
            $output = VeiculoResponse::fromVeiculoModel($veiculoAtualizar);
            $response->getBody()->write($contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidContractException $e) {
            $response->getBody()->write($contractResolver->toJson(ValidationErrorResponse::from($e->getViolations())));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            if ($e->getCode() == SqlStateEnum::DUPLICATE_ENTRY->value) {
                $response->getBody()->write($contractResolver->toJson(new ValidationErrorResponse([
                    new ValidationError("placa", "Veículo já existente para esta placa."),
                ])));
                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }
            throw $e;
        }
    }
}

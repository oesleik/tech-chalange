<?php

declare(strict_types=1);

namespace App\Veiculos\Controller;

use App\Core\Consts\SqlStateEnum;
use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Core\Contract\ValidationError;
use App\Core\Contract\ValidationErrorResponse;
use App\Veiculos\Contract\CriarVeiculoRequest;
use App\Veiculos\Contract\VeiculoResponse;
use App\Veiculos\Service\VeiculoService;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;

class CriarVeiculoController {
    #[OA\Post(
        path: '/veiculos/',
        operationId: 'criarVeiculo',
        summary: 'Cadastrar um novo veículo',
        tags: ['Veículos']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/CriarVeiculoRequest'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Veículo criado',
        content: new OA\JsonContent(ref: '#/components/schemas/VeiculoResponse')
    )]
    #[OA\Response(
        response: 400,
        description: 'Validation error',
        content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
    )]
    #[OA\Response(
        response: 409,
        description: 'Conflict error',
        content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
    )]
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        ContractResolver $contractResolver,
        VeiculoService $service,
    ): ResponseInterface {
        try {
            $payload = json_decode($request->getBody()->getContents(), true);
            $req = $contractResolver->fromArray($payload, CriarVeiculoRequest::class);
            $veiculo = $req->toVeiculoModel();
            $veiculoCriado = $service->criarVeiculo($veiculo);
            $output = VeiculoResponse::fromVeiculoModel($veiculoCriado);
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

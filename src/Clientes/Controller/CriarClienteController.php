<?php

declare(strict_types=1);

namespace App\Clientes\Controller;

use App\Clientes\Contract\ClienteResponse;
use App\Clientes\Contract\CriarClienteRequest;
use App\Clientes\Service\ClienteService;
use App\Core\Database\DatabaseErrorEnum;
use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Core\Contract\ValidationError;
use App\Core\Contract\ValidationErrorResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;
use PDOException;

class CriarClienteController {
    #[OA\Post(
        path: '/clientes/',
        operationId: 'criarCliente',
        summary: 'Cadastrar um novo cliente',
        tags: ['Clientes']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/CriarClienteRequest'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Cliente encontrado',
        content: new OA\JsonContent(ref: '#/components/schemas/ClienteResponse')
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
        ClienteService $service,
    ): ResponseInterface {
        try {
            $payload = json_decode($request->getBody()->getContents(), true);
            $req = $contractResolver->fromArray($payload, CriarClienteRequest::class);

            $cliente = $req->toClienteModel();
            $clienteCriado = $service->criarCliente($cliente);

            $output = ClienteResponse::fromClienteModel($clienteCriado, false);
            $response->getBody()->write($contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidContractException $e) {
            $response->getBody()->write($contractResolver->toJson(ValidationErrorResponse::from($e->getViolations())));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            if (DatabaseErrorEnum::fromPdoException($e) == DatabaseErrorEnum::DUPLICATE_ENTRY) {
                $response->getBody()->write($contractResolver->toJson(new ValidationErrorResponse([
                    new ValidationError("cpf_cnpj", "Cliente já existente para este CPF/CNPJ."),
                ])));

                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }

            throw $e;
        }
    }

}

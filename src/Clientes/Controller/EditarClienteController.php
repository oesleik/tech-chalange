<?php

declare(strict_types=1);

namespace App\Clientes\Controller;

use App\Clientes\Contract\ClienteResponse;
use App\Clientes\Contract\EditarClienteRequest;
use App\Clientes\Model\ClienteModel;
use App\Clientes\Service\ClienteService;
use App\Clientes\ValueObject\CpfOrCnpjValueFactory;
use App\Clientes\ValueObject\EmailValue;
use App\Clientes\ValueObject\TelefoneValue;
use App\Core\Consts\SqlStateEnum;
use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Core\Contract\ValidationError;
use App\Core\Contract\ValidationErrorResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use OpenApi\Attributes as OA;
use PDOException;

class EditarClienteController {
    #[OA\Patch(
        path: '/clientes/{id}',
        operationId: 'editarCliente',
        summary: 'Editar dados de um cliente',
        tags: ['Clientes']
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
            ref: '#/components/schemas/EditarClienteRequest'
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
        response: 404,
        description: 'Cliente não encontrado'
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
        ClienteService $service,
    ): ResponseInterface {
        try {
            $payload = json_decode($request->getBody()->getContents(), true);
            $req = $contractResolver->fromArray($payload, EditarClienteRequest::class);

            $cliente = $service->obterClientePorId($id);

            if (empty($cliente)) {
                return $response->withStatus(404, "Cliente não encontrado");
            }

            $clienteAtualizar = new ClienteModel(
                id: $cliente->getId(),
                nome: $req->nome ?? $cliente->getNome(),
                cpfCnpj: CpfOrCnpjValueFactory::make($req->cpf_cnpj ?? $cliente->getCpfCnpj()->getValue()),
                email: new EmailValue($req->email ?? $cliente->getEmail()->getValue()),
                telefone: new TelefoneValue($req->telefone ?? $cliente->getTelefone()->getValue()),
            );

            $service->atualizarCliente($clienteAtualizar);
            $output = ClienteResponse::fromClienteModel($clienteAtualizar);

            $response->getBody()->write($contractResolver->toJson($output));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidContractException $e) {
            $response->getBody()->write($contractResolver->toJson(ValidationErrorResponse::from($e->getViolations())));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            if ($e->getCode() == SqlStateEnum::DUPLICATE_ENTRY->value) {
                $response->getBody()->write($contractResolver->toJson(new ValidationErrorResponse([
                    new ValidationError("cpf_cnpj", "Cliente já existente para este CPF/CNPJ."),
                ])));

                return $response->withStatus(409)->withHeader('Content-Type', 'application/json');
            }

            throw $e;
        }
    }

}

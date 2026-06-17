<?php

declare(strict_types=1);

namespace App\Estoque\Controller;

use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Estoque\Contract\EntradaEstoqueContract;
use App\Estoque\Repository\EstoqueRepository;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class EstoqueController {
    public function __construct(
        private readonly EstoqueRepository $repository,
        private readonly ContractResolver  $contractResolver,
    ) {}

    #[OA\Post(
        path: '/estoque/entrada',
        summary: 'Registrar entrada de peças no estoque',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/EntradaEstoqueRequest')
        ),
        tags: ['Estoque'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Entrada registrada com sucesso',
                content: new OA\JsonContent(ref: '#/components/schemas/EntradaEstoqueResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Peça não encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Dados inválidos',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function registrarEntrada(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $body     = (array) $request->getParsedBody();
            $contract = $this->contractResolver->fromArray($body, EntradaEstoqueContract::class);
            $entrada  = $this->repository->registrarEntrada($contract->id_peca, $contract->quantidade);

            $response->getBody()->write(json_encode($entrada));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (InvalidContractException $e) {
            $errors = [];
            foreach ($e->getViolations() as $violation) {
                $field          = trim($violation->getPropertyPath(), '[]');
                $errors[$field] = $violation->getMessage();
            }

            $response->getBody()->write(json_encode(['errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);

        } catch (\RuntimeException $e) {
            $status = $e->getCode() === 404 ? 404 : 400;
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
        }
    }

    #[OA\Get(
        path: '/estoque/pecas/{id}',
        summary: 'Consultar estoque atual de uma peça',
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        tags: ['Estoque'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estoque atual da peça',
                content: new OA\JsonContent(ref: '#/components/schemas/ConsultaEstoqueResponse')
            ),
            new OA\Response(response: 404, description: 'Peça não encontrada'),
        ]
    )]
    public function consultarEstoque(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $id_peca = (int) $request->getAttribute('id'); // ← aqui, sem $args
            $estoque = $this->repository->consultarEstoquePorPeca($id_peca);

            $response->getBody()->write(json_encode($estoque));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (\RuntimeException $e) {
            $status = $e->getCode() === 404 ? 404 : 400;
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
        }
    }
}

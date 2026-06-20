<?php

declare(strict_types=1);

namespace App\Estoque\Controller;

use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Estoque\Contract\BaixaEstoqueContract;
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
            new OA\Response(response: 404, description: 'Peça não encontrada'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
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

            return $this->jsonResponse($response, $entrada, 201);

        } catch (InvalidContractException $e) {
            return $this->validationErrorResponse($response, $e);
        } catch (\RuntimeException $e) {
            return $this->runtimeErrorResponse($response, $e);
        }
    }

    #[OA\Post(
        path: '/estoque/baixa',
        summary: 'Registrar baixa de peças no estoque',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BaixaEstoqueRequest')
        ),
        tags: ['Estoque'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Baixa registrada com sucesso',
                content: new OA\JsonContent(ref: '#/components/schemas/BaixaEstoqueResponse')
            ),
            new OA\Response(response: 404, description: 'Peça não encontrada'),
            new OA\Response(response: 409, description: 'Estoque insuficiente'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ]
    )]
    public function registrarBaixa(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $body     = (array) $request->getParsedBody();
            $contract = $this->contractResolver->fromArray($body, BaixaEstoqueContract::class);
            $baixa    = $this->repository->registrarBaixa($contract->id_peca, $contract->quantidade);

            return $this->jsonResponse($response, $baixa, 201);

        } catch (InvalidContractException $e) {
            return $this->validationErrorResponse($response, $e);
        } catch (\RuntimeException $e) {
            return $this->runtimeErrorResponse($response, $e);
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
            $id_peca = (int) $request->getAttribute('id');
            $estoque = $this->repository->consultarEstoquePorPeca($id_peca);

            return $this->jsonResponse($response, $estoque, 200);

        } catch (\RuntimeException $e) {
            return $this->runtimeErrorResponse($response, $e);
        }
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status): ResponseInterface {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    private function validationErrorResponse(
        ResponseInterface $response,
        InvalidContractException $e,
    ): ResponseInterface {
        $errors = [];
        foreach ($e->getViolations() as $violation) {
            $field          = trim($violation->getPropertyPath(), '[]');
            $errors[$field] = $violation->getMessage();
        }

        return $this->jsonResponse($response, ['errors' => $errors], 422);
    }

    private function runtimeErrorResponse(ResponseInterface $response, \RuntimeException $e): ResponseInterface {
        $status = match ($e->getCode()) {
            404     => 404,
            409     => 409,
            default => 400,
        };

        return $this->jsonResponse($response, ['error' => $e->getMessage()], $status);
    }
}

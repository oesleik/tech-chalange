<?php

declare(strict_types=1);

namespace App\Estoque\Controller;

use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Core\Contract\ValidationErrorResponse;
use App\Estoque\Contract\EstoquePecaResponse;
use App\Estoque\Contract\LancamentoEstoqueRequest;
use App\Estoque\Contract\LancamentoEstoqueResponse;
use App\Estoque\Service\EstoqueInsuficienteException;
use App\Estoque\Service\EstoqueService;
use App\Estoque\Service\PecaNaoEncontradaException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class EstoqueController {
    public function __construct(
        private readonly EstoqueService $service,
        private readonly ContractResolver  $contractResolver,
    ) {}

    #[OA\Post(
        path: '/estoque/entrada',
        summary: 'Registrar entrada de peças no estoque',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LancamentoEstoqueRequest')
        ),
        tags: ['Estoque'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Entrada registrada com sucesso',
                content: new OA\JsonContent(ref: '#/components/schemas/LancamentoEstoqueResponse')
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
            $payload = json_decode($request->getBody()->getContents(), true);
            $contract = $this->contractResolver->fromArray($payload, LancamentoEstoqueRequest::class);
            $entrada  = $this->service->registrarEntrada($contract->id_peca, $contract->quantidade);
            $res = LancamentoEstoqueResponse::fromLancamentoModel($entrada);

            $response->getBody()->write($this->contractResolver->toJson($res));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidContractException $e) {
            $response->getBody()->write($this->contractResolver->toJson(ValidationErrorResponse::from($e->getViolations())));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        } catch (PecaNaoEncontradaException $e) {
            return $response->withStatus(404, "Peça não encontrada");
        }
    }

    #[OA\Post(
        path: '/estoque/baixa',
        summary: 'Registrar baixa de peças no estoque',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LancamentoEstoqueRequest')
        ),
        tags: ['Estoque'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Baixa registrada com sucesso',
                content: new OA\JsonContent(ref: '#/components/schemas/LancamentoEstoqueResponse')
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
            $payload = json_decode($request->getBody()->getContents(), true);
            $contract = $this->contractResolver->fromArray($payload, LancamentoEstoqueRequest::class);
            $baixa    = $this->service->registrarBaixa($contract->id_peca, $contract->quantidade);
            $res = LancamentoEstoqueResponse::fromLancamentoModel($baixa);

            $response->getBody()->write($this->contractResolver->toJson($res));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (InvalidContractException $e) {
            $response->getBody()->write($this->contractResolver->toJson(ValidationErrorResponse::from($e->getViolations())));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        } catch (PecaNaoEncontradaException $e) {
            return $response->withStatus(404, "Peça não encontrada");
        } catch (EstoqueInsuficienteException $e) {
            return $response->withStatus(422, "Estoque insuficiente");
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
                content: new OA\JsonContent(ref: '#/components/schemas/EstoquePecaResponse')
            ),
            new OA\Response(response: 404, description: 'Peça não encontrada'),
        ]
    )]
    public function consultarEstoque(
        int $id,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $estoque = $this->service->consultarEstoquePorPeca($id);
            $res = EstoquePecaResponse::fromLancamentoModel($estoque);

            $response->getBody()->write($this->contractResolver->toJson($res));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PecaNaoEncontradaException $e) {
            return $response->withStatus(404, "Peça não encontrada");
        }
    }

}

<?php

declare(strict_types=1);

namespace App\Estoque\Presentation\Http\Controller;

use App\Estoque\Application\UseCase\ConsultarEstoquePorPeca\ConsultarEstoquePorPecaUseCase;
use App\Estoque\Domain\Exception\PecaNaoEncontradaException;
use App\Estoque\Presentation\Http\DTO\EstoqueConsultaResponseDTO;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Estoque\Application\UseCase\ConsultarEstoquePorPeca\ConsultarEstoquePorPecaUseCaseInterface;

final class ConsultarEstoquePorPecaController implements ConsultarEstoquePorPecaControllerInterface
{
    public function __construct(
        private readonly ConsultarEstoquePorPecaUseCaseInterface $useCase,
        private readonly PresenterInterface $presenter,
    ) {}

    #[OA\Get(
        path: '/estoque/pecas/{id}',
        summary: 'Consultar estoque atual de uma peça',
        tags: ['Estoque'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Estoque atual da peça',
                content: new OA\JsonContent(ref: '#/components/schemas/EstoqueConsultaResponseDTO')),
            new OA\Response(response: 404, description: 'Peça não encontrada'),
        ]
    )]
    public function execute(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        try {
            // no Slim 4 os parâmetros de rota ficam nos atributos do request
            $pecaId = (int) $request->getAttribute('id');
            $output = $this->useCase->executar($pecaId);

            return $this->presenter->success($response, EstoqueConsultaResponseDTO::fromOutputDTO($output), HttpStatusCodeEnum::Ok);
        } catch (PecaNaoEncontradaException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::NotFound);
        }
    }
}
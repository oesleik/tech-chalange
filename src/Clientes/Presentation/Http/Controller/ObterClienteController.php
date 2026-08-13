<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\Controller;

use App\Clientes\Application\UseCase\ObterCliente\ObterClienteUseCaseInterface;
use App\Clientes\Domain\Exception\ClienteNaoEncontradoException;
use App\Clientes\Presentation\Http\DTO\ClienteResponseDTO;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

final class ObterClienteController implements ObterClienteControllerInterface {
    public function __construct(
        private readonly ObterClienteUseCaseInterface $useCase,
        private readonly PresenterInterface $presenter,
    ) {}

    #[OA\Get(
        path: '/clientes/{id}',
        operationId: 'obterCliente',
        summary: 'Obter detalhes de um cliente',
        tags: ['Clientes']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Cliente encontrado',
        content: new OA\JsonContent(ref: ClienteResponseDTO::class)
    )]
    #[OA\Response(response: 404, description: 'Cliente não encontrado')]
    public function execute(int $id, ResponseInterface $response): ResponseInterface {
        try {
            $cliente = $this->useCase->executar($id);

            return $this->presenter->success(
                $response,
                ClienteResponseDTO::fromEntity($cliente),
                HttpStatusCodeEnum::Ok,
            );
        } catch (ClienteNaoEncontradoException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::NotFound);
        }
    }
}

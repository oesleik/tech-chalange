<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\Controller;

use App\Clientes\Application\UseCase\ListarClientes\ListarClientesUseCaseInterface;
use App\Clientes\Presentation\Http\DTO\ListagemClientesResponseDTO;
use App\Clientes\Presentation\Http\DTO\ListarClientesMapper;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListarClientesController implements ListarClientesControllerInterface {
    public function __construct(
        private readonly ListarClientesUseCaseInterface $useCase,
        private readonly PresenterInterface $presenter,
    ) {}

    #[OA\Get(
        path: '/clientes/',
        operationId: 'listarClientes',
        summary: 'Listar clientes cadastrados',
        tags: ['Clientes']
    )]
    #[OA\Parameter(
        name: 'cpf_cnpj',
        in: 'query',
        required: false,
        description: 'Filtra pelo CPF ou CNPJ do cliente',
        schema: new OA\Schema(type: 'string', example: '123.456.789-09')
    )]
    #[OA\Response(
        response: 200,
        description: 'Lista de clientes',
        content: new OA\JsonContent(ref: ListagemClientesResponseDTO::class)
    )]
    #[OA\Response(response: 400, description: 'Parâmetro inválido')]
    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        try {
            $clientes = $this->useCase->executar(
                ListarClientesMapper::fromQueryParams($request->getQueryParams()),
            );

            return $this->presenter->success(
                $response,
                ListagemClientesResponseDTO::fromEntities($clientes),
                HttpStatusCodeEnum::Ok,
            );
        } catch (InvalidArgumentException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::BadRequest);
        }
    }
}

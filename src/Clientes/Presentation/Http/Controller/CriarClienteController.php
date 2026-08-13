<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\Controller;

use App\Clientes\Application\UseCase\CriarCliente\CriarClienteUseCaseInterface;
use App\Clientes\Domain\Exception\ClienteJaCadastradoException;
use App\Clientes\Presentation\Http\DTO\ClienteResponseDTO;
use App\Clientes\Presentation\Http\DTO\CriarClienteMapper;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class CriarClienteController implements CriarClienteControllerInterface {
    public function __construct(
        private readonly CriarClienteUseCaseInterface $useCase,
        private readonly PresenterInterface $presenter,
    ) {}

    #[OA\Post(
        path: '/clientes/',
        operationId: 'criarCliente',
        summary: 'Cadastrar um novo cliente',
        tags: ['Clientes']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: CriarClienteMapper::class)
    )]
    #[OA\Response(
        response: 201,
        description: 'Cliente criado com sucesso',
        content: new OA\JsonContent(ref: ClienteResponseDTO::class)
    )]
    #[OA\Response(response: 400, description: 'Dados inválidos')]
    #[OA\Response(response: 409, description: 'Cliente já cadastrado')]
    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        try {
            $payload = (array) json_decode($request->getBody()->getContents(), true);
            $cliente = $this->useCase->executar(CriarClienteMapper::parse($payload));

            return $this->presenter->success(
                $response,
                ClienteResponseDTO::fromEntity($cliente),
                HttpStatusCodeEnum::Created,
            );
        } catch (ClienteJaCadastradoException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::Conflict);
        } catch (InvalidArgumentException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::BadRequest);
        }
    }
}

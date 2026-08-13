<?php

declare(strict_types=1);

namespace App\Clientes\Presentation\Http\Controller;

use App\Clientes\Application\UseCase\EditarCliente\EditarClienteUseCaseInterface;
use App\Clientes\Domain\Exception\ClienteJaCadastradoException;
use App\Clientes\Domain\Exception\ClienteNaoEncontradoException;
use App\Clientes\Presentation\Http\DTO\ClienteResponseDTO;
use App\Clientes\Presentation\Http\DTO\EditarClienteMapper;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EditarClienteController implements EditarClienteControllerInterface {
    public function __construct(
        private readonly EditarClienteUseCaseInterface $useCase,
        private readonly PresenterInterface $presenter,
    ) {}

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
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: EditarClienteMapper::class)
    )]
    #[OA\Response(
        response: 200,
        description: 'Cliente atualizado com sucesso',
        content: new OA\JsonContent(ref: ClienteResponseDTO::class)
    )]
    #[OA\Response(response: 400, description: 'Dados inválidos')]
    #[OA\Response(response: 404, description: 'Cliente não encontrado')]
    #[OA\Response(response: 409, description: 'Cliente já cadastrado')]
    public function execute(int $id, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        try {
            $payload = (array) json_decode($request->getBody()->getContents(), true);
            $cliente = $this->useCase->executar($id, EditarClienteMapper::parse($payload));

            return $this->presenter->success(
                $response,
                ClienteResponseDTO::fromEntity($cliente),
                HttpStatusCodeEnum::Ok,
            );
        } catch (ClienteNaoEncontradoException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::NotFound);
        } catch (ClienteJaCadastradoException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::Conflict);
        } catch (InvalidArgumentException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::BadRequest);
        }
    }
}

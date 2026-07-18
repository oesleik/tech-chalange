<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\Veiculos\Application\UseCase\EditarVeiculo\EditarVeiculoUseCase;
use App\Veiculos\Domain\Exception\VeiculoJaCadastradoException;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
use App\Veiculos\Presentation\Http\DTO\EditarVeiculoMapper;
use App\Veiculos\Presentation\Http\DTO\VeiculoResponseDTO;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ServerRequestInterface;

final class EditarVeiculoController {
    public function __construct(
        private EditarVeiculoUseCase $useCase,
        private EditarVeiculoMapper $mapper,
        private PresenterInterface $presenter,
    ) {}

    #[OA\Patch(
        path: '/veiculos/{id}',
        operationId: 'editarVeiculo',
        summary: 'Editar um veículo',
        tags: ['Veículos']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Identificador do veículo',
        schema: new OA\Schema(
            type: 'integer',
            example: 1
        )
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: EditarVeiculoMapper::class
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Veículo atualizado com sucesso',
        content: new OA\JsonContent(
            ref: VeiculoResponseDTO::class
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Dados inválidos'
    )]
    #[OA\Response(
        response: 404,
        description: 'Veículo não encontrado'
    )]
    #[OA\Response(
        response: 409,
        description: 'Já existe um veículo cadastrado com a placa informada'
    )]
    public function execute(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $payload = (array) json_decode(
                $request->getBody()->getContents(),
                true
            );

            $input = $this->mapper->parse($payload);

            $veiculo = $this->useCase->executar(
                $id,
                $input,
            );

            return $this->presenter->success(
                $response,
                VeiculoResponseDTO::fromEntity($veiculo),
            );
        } catch (VeiculoNaoEncontradoException $e) {
            return $this->presenter->error(
                $response,
                $e->getMessage(),
                HttpStatusCodeEnum::NotFound,
            );
        } catch (VeiculoJaCadastradoException $e) {
            return $this->presenter->error(
                $response,
                $e->getMessage(),
                HttpStatusCodeEnum::Conflict,
            );
        } catch (InvalidArgumentException $e) {
            return $this->presenter->error(
                $response,
                $e->getMessage(),
                HttpStatusCodeEnum::BadRequest,
            );
        }
    }
}

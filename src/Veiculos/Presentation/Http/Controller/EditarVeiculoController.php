<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\Controller;

use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use App\Core\Infrastructure\Presentation\HttpStatusCodeEnum;
use App\Core\Infrastructure\Presentation\PresenterInterface;
use App\Veiculos\Application\UseCase\EditarVeiculo\EditarVeiculoInputDTO;
use App\Veiculos\Application\UseCase\EditarVeiculo\EditarVeiculoUseCase;
use App\Veiculos\Domain\Exception\VeiculoJaCadastradoException;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
use App\Veiculos\Infrastructure\Persistence\VeiculoGateway;
use App\Veiculos\Presentation\Http\DTO\VeiculoResponseDTO;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ServerRequestInterface;

final class EditarVeiculoController {
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
            ref: EditarVeiculoInputDTO::class
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
    public function __invoke(
        int $id,
        ServerRequestInterface $request,
        ResponseInterface $response,
        PresenterInterface $presenter,
        DbConnectionInterface $dbConnection,
    ): ResponseInterface {
        try {
            $payload = json_decode($request->getBody()->getContents(), true);
            $input = EditarVeiculoInputDTO::fromArray($payload);

            $veiculoGateway = new VeiculoGateway($dbConnection);
            $useCase = new EditarVeiculoUseCase($veiculoGateway);
            $veiculo = $useCase->executar($id, $input);
        } catch (VeiculoNaoEncontradoException $e) {
            return $presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::NotFound);
        } catch (VeiculoJaCadastradoException $e) {
            return $presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::Conflict);
        } catch (InvalidArgumentException $e) {
            return $presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::BadRequest);
        }

        return $presenter->success($response, VeiculoResponseDTO::fromEntity($veiculo));
    }
}

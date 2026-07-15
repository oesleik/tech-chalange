<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\Controller;

use App\Core\Infrastructure\Persistence\DbConnectionInterface;
use App\Core\Infrastructure\Presentation\HttpStatusCodeEnum;
use App\Core\Infrastructure\Presentation\PresenterInterface;
use App\Veiculos\Application\UseCase\CriarVeiculo\CriarVeiculoUseCase;
use App\Veiculos\Domain\Exception\VeiculoJaCadastradoException;
use App\Veiculos\Infrastructure\Persistence\VeiculoGateway;
use App\Veiculos\Presentation\Http\DTO\CriarVeiculoMapper;
use App\Veiculos\Presentation\Http\DTO\VeiculoResponseDTO;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ServerRequestInterface;

final class CriarVeiculoController {
    #[OA\Post(
        path: '/veiculos/',
        operationId: 'criarVeiculo',
        summary: 'Cadastrar um novo veículo',
        tags: ['Veículos']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: CriarVeiculoMapper::class
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Veículo criado com sucesso',
        content: new OA\JsonContent(
            ref: VeiculoResponseDTO::class
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Erro de validação'
    )]
    #[OA\Response(
        response: 409,
        description: 'Veículo já cadastrado'
    )]
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        PresenterInterface $presenter,
        DbConnectionInterface $dbConnection,
    ): ResponseInterface {
        try {
            $payload = json_decode($request->getBody()->getContents(), true);
            $veiculoParaCriar = CriarVeiculoMapper::parse($payload);
            $veiculosGateway = new VeiculoGateway($dbConnection);
            $useCase = new CriarVeiculoUseCase($veiculosGateway);
            $veiculo = $useCase->executar($veiculoParaCriar);
        } catch (VeiculoJaCadastradoException $e) {
            return $presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::Conflict);
        } catch (InvalidArgumentException $e) {
            return $presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::BadRequest);
        }

        return $presenter->success($response, VeiculoResponseDTO::fromEntity($veiculo));
    }
}

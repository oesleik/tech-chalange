<?php

declare(strict_types=1);

namespace App\Veiculos\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\Veiculos\Application\UseCase\CriarVeiculo\CriarVeiculoUseCase;
use App\Veiculos\Domain\Exception\VeiculoJaCadastradoException;
use App\Veiculos\Presentation\Http\DTO\CriarVeiculoMapper;
use App\Veiculos\Presentation\Http\DTO\VeiculoResponseDTO;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ServerRequestInterface;

final class CriarVeiculoController {
    public function __construct(
        private CriarVeiculoUseCase $useCase,
        private CriarVeiculoMapper $mapper,
        private PresenterInterface $presenter,
    ) {}

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
    public function execute(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        try {
            $payload = (array) json_decode(
                $request->getBody()->getContents(),
                true
            );

            $veiculo = $this->mapper->parse($payload);

            $veiculoCriado = $this->useCase->executar($veiculo);

            return $this->presenter->success(
                $response,
                VeiculoResponseDTO::fromEntity($veiculoCriado),
                HttpStatusCodeEnum::Created,
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

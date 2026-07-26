<?php

declare(strict_types=1);

namespace App\Estoque\Presentation\Http\Controller;

use App\Estoque\Application\UseCase\RegistrarBaixaEstoque\RegistrarBaixaEstoqueUseCase;
use App\Estoque\Domain\Exception\EstoqueInsuficienteException;
use App\Estoque\Domain\Exception\PecaNaoEncontradaException;
use App\Estoque\Presentation\Http\DTO\LancamentoResponseDTO;
use App\Estoque\Presentation\Http\DTO\RegistrarBaixaEstoqueMapper;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use App\Estoque\Application\UseCase\RegistrarBaixaEstoque\RegistrarBaixaEstoqueUseCaseInterface;

final class RegistrarBaixaEstoqueController implements RegistrarBaixaEstoqueControllerInterface {
    public function __construct(
        private readonly RegistrarBaixaEstoqueUseCaseInterface $useCase,
        private readonly PresenterInterface $presenter,
    ) {}

    #[OA\Post(
        path: '/estoque/baixa',
        summary: 'Registrar baixa de peças no estoque',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LancamentoResponseDTO')
        ),
        tags: ['Estoque'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Baixa registrada com sucesso',
                content: new OA\JsonContent(ref: '#/components/schemas/LancamentoResponseDTO')
            ),
            new OA\Response(response: 400, description: 'Dados inválidos'),
            new OA\Response(response: 404, description: 'Peça não encontrada'),
            new OA\Response(response: 422, description: 'Estoque insuficiente'),
        ]
    )]
    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        try {
            $payload = (array) json_decode($request->getBody()->getContents(), true);
            $lancamento = $this->useCase->executar(RegistrarBaixaEstoqueMapper::parse($payload));

            return $this->presenter->success($response, LancamentoResponseDTO::fromEntity($lancamento), HttpStatusCodeEnum::Ok);
        } catch (PecaNaoEncontradaException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::NotFound);
        } catch (EstoqueInsuficienteException $e) {
            // saldo insuficiente é regra de negócio — por isso 422, não 400
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::UnprocessableEntity);
        } catch (\InvalidArgumentException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::BadRequest);
        }
    }
}

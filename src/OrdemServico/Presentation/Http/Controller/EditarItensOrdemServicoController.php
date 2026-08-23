<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\EditarItensOrdemServico\EditarPecasOrdemServicoUseCaseInterface;
use App\OrdemServico\Application\UseCase\EditarItensOrdemServico\EditarServicosOrdemServicoUseCaseInterface;
use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoUseCaseInterface;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use App\OrdemServico\Presentation\Http\DTO\EditarItensOrdemServicoMapper;
use App\OrdemServico\Presentation\Http\DTO\OrdemServicoCompletaResponseDTO;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Post(path: '/ordens-servico/{id}/pecas', operationId: 'adicionarPecasOrdemServico', summary: 'Adicionar peças', tags: ['Ordens de Serviço - Peças e Serviços'])]
#[OA\Put(path: '/ordens-servico/{id}/pecas', operationId: 'atualizarPecasOrdemServico', summary: 'Atualizar peças (substitui todas as atuais)', tags: ['Ordens de Serviço - Peças e Serviços'])]
#[OA\Post(path: '/ordens-servico/{id}/servicos', operationId: 'adicionarServicosOrdemServico', summary: 'Adicionar serviços', tags: ['Ordens de Serviço - Peças e Serviços'])]
#[OA\Put(path: '/ordens-servico/{id}/servicos', operationId: 'atualizarServicosOrdemServico', summary: 'Atualizar serviços (substitui todos os atuais)', tags: ['Ordens de Serviço - Peças e Serviços'])]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
#[OA\Response(response: 200, description: 'Itens atualizados', content: new OA\JsonContent(ref: OrdemServicoCompletaResponseDTO::class))]
#[OA\Response(response: 400, description: 'Dados inválidos')]
#[OA\Response(response: 404, description: 'Não encontrada')]
#[OA\Response(response: 422, description: 'Ordem já finalizada')]
final class EditarItensOrdemServicoController {
    public function __construct(
        private readonly EditarPecasOrdemServicoUseCaseInterface $editarPecasUseCase,
        private readonly EditarServicosOrdemServicoUseCaseInterface $editarServicosUseCase,
        private readonly ObterOrdemServicoUseCaseInterface $obterUseCase,
        private readonly PresenterInterface $presenter,
    ) {}

    public function adicionarPecas(int $id, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->editarPecas($id, $request, $response, false);
    }

    public function atualizarPecas(int $id, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->editarPecas($id, $request, $response, true);
    }

    public function adicionarServicos(int $id, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->editarServicos($id, $request, $response, false);
    }

    public function atualizarServicos(int $id, ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $this->editarServicos($id, $request, $response, true);
    }

    private function editarPecas(int $id, ServerRequestInterface $request, ResponseInterface $response, bool $substituir): ResponseInterface {
        try {
            $payload = (array) json_decode($request->getBody()->getContents(), true);
            $input   = EditarItensOrdemServicoMapper::parse($payload, 'id_peca', $id, $substituir);

            $this->verificarNaoFinalizada($id);
            $this->editarPecasUseCase->executar($input);

            return $this->presenter->success($response, OrdemServicoCompletaResponseDTO::fromOutputDTO($this->obterUseCase->executar($id)), HttpStatusCodeEnum::Ok);
        } catch (OrdemServicoNaoEncontradaException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::NotFound);
        } catch (InvalidArgumentException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::BadRequest);
        }
    }

    private function editarServicos(int $id, ServerRequestInterface $request, ResponseInterface $response, bool $substituir): ResponseInterface {
        try {
            $payload = (array) json_decode($request->getBody()->getContents(), true);
            $input   = EditarItensOrdemServicoMapper::parse($payload, 'id_servico', $id, $substituir);

            $this->verificarNaoFinalizada($id);
            $this->editarServicosUseCase->executar($input);

            return $this->presenter->success($response, OrdemServicoCompletaResponseDTO::fromOutputDTO($this->obterUseCase->executar($id)), HttpStatusCodeEnum::Ok);
        } catch (OrdemServicoNaoEncontradaException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::NotFound);
        } catch (InvalidArgumentException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::BadRequest);
        }
    }

    private function verificarNaoFinalizada(int $id): void {
        $output = $this->obterUseCase->executar($id);

        if ($output->ordemServico->situacao()->estaFinalizada()) {
            throw new InvalidArgumentException('A ordem de serviço já está finalizada.');
        }
    }
}

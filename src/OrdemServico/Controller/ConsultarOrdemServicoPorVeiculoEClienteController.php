<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\Clientes\Service\ClienteService;
use App\Clientes\ValueObject\CpfOrCnpjValueFactory;
use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\OrdemServico\Contract\ConsultarOrdemServicoPorVeiculoEClienteRequest;
use App\OrdemServico\Contract\ConsultarOrdemServicoPorVeiculoRequest;
use App\OrdemServico\Contract\OrdemServicoCompletaResponse;
use App\OrdemServico\Service\ItensOrdemServicoService;
use App\OrdemServico\Service\OrdemServicoService;
use App\Veiculos\Service\VeiculoService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Get(
    path: '/consulta/ordem-servico',
    operationId: 'consultarOrdemServicoPorVeiculo',
    summary: 'Consulta pública de Ordem de Serviço por CPF/CNPJ e placa do veículo',
    description: 'Rota pública. Retorna a Ordem de Serviço mais recente vinculada ao cliente (CPF ou CNPJ) e ao veículo (placa) informados.',
    tags: ['Consulta Pública']
)]
#[OA\Parameter(
    name: 'cpf_cnpj',
    in: 'query',
    required: true,
    description: 'CPF (11 dígitos) ou CNPJ (14 dígitos) do cliente, com ou sem formatação',
    schema: new OA\Schema(type: 'string', example: '123.456.789-09')
)]
#[OA\Parameter(
    name: 'placa',
    in: 'query',
    required: true,
    description: 'Placa do veículo (formato antigo AAA-1234 ou Mercosul AAA1A23)',
    schema: new OA\Schema(type: 'string', example: 'ABC-1234')
)]
#[OA\Response(
    response: 200,
    description: 'Ordem de Serviço encontrada',
    content: new OA\JsonContent(ref: '#/components/schemas/OrdemServicoCompletaResponse')
)]
#[OA\Response(response: 400, description: 'Parâmetros inválidos — CPF/CNPJ ou placa com formato incorreto')]
#[OA\Response(response: 404, description: 'Cliente, veículo ou Ordem de Serviço não encontrados')]
class ConsultarOrdemServicoPorVeiculoEClienteController {
    public function __construct(
        private ContractResolver $contractResolver,
        private ClienteService $clienteService,
        private VeiculoService $veiculoService,
        private OrdemServicoService $ordemServicoService,
        private ItensOrdemServicoService $itensOrdemServicoService,
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        try {
            /** @var ConsultarOrdemServicoPorVeiculoEClienteRequest $input */
            $input = $this->contractResolver->fromArray(
                $request->getQueryParams(),
                ConsultarOrdemServicoPorVeiculoEClienteRequest::class,
            );
        } catch (InvalidContractException $e) {
            return $this->erro($response, $e->getViolations(), 400);
        }

        $clientes = $this->clienteService->listarClientes(CpfOrCnpjValueFactory::make($input->cpf_cnpj));
        $cliente = $clientes[0] ?? null;

        if ($cliente === null) {
            return $this->erroSimples($response, 'Cliente não encontrado para o CPF/CNPJ informado.', 404);
        }

        $veiculo = $this->veiculoService->obterVeiculoPorPlaca($input->placa);
        if ($veiculo === null) {
            return $this->erroSimples($response, 'Veículo não encontrado para a placa informada.', 404);
        }

        $ordemServico = $this->ordemServicoService->obterOrdemServicoPorClienteEVeiculo(
            $cliente->getId(),
            $veiculo->getId(),
        );

        if ($ordemServico === null) {
            return $this->erroSimples(
                $response,
                'Nenhuma Ordem de Serviço encontrada para este cliente e veículo.',
                404,
            );
        }

        $pecas    = $this->itensOrdemServicoService->obterPecasPorIdOrdemServico($ordemServico->getId());
        $servicos = $this->itensOrdemServicoService->obterServicosPorIdOrdemServico($ordemServico->getId());

        $output = OrdemServicoCompletaResponse::fromModel($ordemServico, $pecas, $servicos);

        $response->getBody()->write($this->contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function erro(ResponseInterface $response, iterable $violations, int $status): ResponseInterface {
        $erros = [];
        foreach ($violations as $violation) {
            $campo = trim($violation->getPropertyPath(), '[]');
            $erros[$campo][] = $violation->getMessage();
        }

        $response->getBody()->write(json_encode(['erros' => $erros]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    private function erroSimples(ResponseInterface $response, string $mensagem, int $status): ResponseInterface {
        $response->getBody()->write(json_encode(['erro' => $mensagem]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}

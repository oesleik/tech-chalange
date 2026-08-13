<?php

declare(strict_types=1);

namespace App\OrdemServico\Controller;

use App\Clientes\Application\UseCase\ListarClientes\ListarClientesInputDTO;
use App\Clientes\Application\UseCase\ListarClientes\ListarClientesUseCaseInterface;
use App\Core\Contract\ContractResolver;
use App\Core\Contract\InvalidContractException;
use App\Core\Contract\ValidationErrorResponse;
use App\OrdemServico\Contract\ConsultarOrdemServicoPorVeiculoEClienteRequest;
use App\OrdemServico\Contract\OrdemServicoCompletaResponse;
use App\OrdemServico\Model\FiltroOrdemServico;
use App\OrdemServico\Service\ItensOrdemServicoService;
use App\OrdemServico\Service\OrdemServicoService;
use App\Veiculos\Application\UseCase\ObterVeiculoPorPlaca\ObterVeiculoPorPlacaUseCase;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
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
        private ListarClientesUseCaseInterface $clienteUseCase,
        private ObterVeiculoPorPlacaUseCase $obterVeiculoPorPlacaUseCase,
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
            $response->getBody()->write($this->contractResolver->toJson(ValidationErrorResponse::from($e->getViolations())));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $clientes = $this->clienteUseCase->executar(new ListarClientesInputDTO($input->cpf_cnpj));
        $cliente = $clientes[0] ?? null;

        if ($cliente === null || empty($cliente->getId())) {
            return $response->withStatus(404, "Cliente não encontrado para o CPF/CNPJ informado");
        }

        try {
            $veiculo = $this->obterVeiculoPorPlacaUseCase->executar($input->placa);
        } catch (VeiculoNaoEncontradoException) {
            return $response->withStatus(404, "Veículo não encontrado para a placa informada");
        }

        $filtros = new FiltroOrdemServico(
            idCliente: $cliente->getId(),
            idVeiculo: $veiculo->id(),
            limit: 1,
        );

        $ordemServico = $this->ordemServicoService->listarOrdensServico($filtros)[0] ?? null;

        if ($ordemServico === null) {
            return $response->withStatus(404, "Ordem de Serviço não encontrada para este cliente e veículo");
        }

        $pecas    = $this->itensOrdemServicoService->obterPecasPorIdOrdemServico($ordemServico->getId());
        $servicos = $this->itensOrdemServicoService->obterServicosPorIdOrdemServico($ordemServico->getId());

        $output = OrdemServicoCompletaResponse::fromModel($ordemServico, $pecas, $servicos);

        $response->getBody()->write($this->contractResolver->toJson($output));
        return $response->withHeader('Content-Type', 'application/json');
    }

}

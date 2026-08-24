<?php

declare(strict_types=1);

namespace App\OrdemServico\Presentation\Http\Controller;

use App\Clientes\Application\UseCase\ListarClientes\ListarClientesInputDTO;
use App\Clientes\Application\UseCase\ListarClientes\ListarClientesUseCaseInterface;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoOutputDTO;
use App\OrdemServico\Domain\ValueObject\FiltroOrdemServico;
use App\OrdemServico\Presentation\Http\DTO\OrdemServicoCompletaResponseDTO;
use App\Veiculos\Application\UseCase\ObterVeiculoPorPlaca\ObterVeiculoPorPlacaUseCase;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Get(path: '/consulta/ordem-servico', operationId: 'consultarOrdemServicoPorVeiculo', summary: 'Consulta pública de OS por CPF/CNPJ e placa', description: 'Rota pública. Retorna a OS mais recente do cliente e veículo informados.', tags: ['Consulta Pública'])]
#[OA\Parameter(name: 'cpf_cnpj', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: '123.456.789-09'))]
#[OA\Parameter(name: 'placa', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: 'ABC-1234'))]
#[OA\Response(response: 200, description: 'OS encontrada', content: new OA\JsonContent(ref: OrdemServicoCompletaResponseDTO::class))]
#[OA\Response(response: 400, description: 'Parâmetros inválidos')]
#[OA\Response(response: 404, description: 'Cliente, veículo ou OS não encontrados')]
final class ConsultarOrdemServicoPorVeiculoEClienteController {
    public function __construct(
        private readonly ListarClientesUseCaseInterface $clienteUseCase,
        private readonly ObterVeiculoPorPlacaUseCase $obterVeiculoPorPlacaUseCase,
        private readonly OrdemServicoGatewayInterface $ordemServicoGateway,
        private readonly ItensOrdemServicoGatewayInterface $itensGateway,
        private readonly PresenterInterface $presenter,
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        $params   = $request->getQueryParams();
        $cpfCnpj  = trim($params['cpf_cnpj'] ?? '');
        $placa    = trim($params['placa'] ?? '');

        if ($cpfCnpj === '' || $placa === '') {
            return $this->presenter->error($response, 'Os campos cpf_cnpj e placa são obrigatórios.', HttpStatusCodeEnum::BadRequest);
        }

        try {
            $clientes = $this->clienteUseCase->executar(new ListarClientesInputDTO($cpfCnpj));
        } catch (InvalidArgumentException $e) {
            return $this->presenter->error($response, $e->getMessage(), HttpStatusCodeEnum::BadRequest);
        }

        $cliente = $clientes[0] ?? null;
        if ($cliente === null) {
            return $this->presenter->error($response, 'Cliente não encontrado para o CPF/CNPJ informado.', HttpStatusCodeEnum::NotFound);
        }

        try {
            $veiculo = $this->obterVeiculoPorPlacaUseCase->executar($placa);
        } catch (VeiculoNaoEncontradoException) {
            return $this->presenter->error($response, 'Veículo não encontrado para a placa informada.', HttpStatusCodeEnum::NotFound);
        }

        $lista = $this->ordemServicoGateway->listar(new FiltroOrdemServico(
            idCliente: $cliente->id(),
            idVeiculo: $veiculo->id(),
            limit: 1,
        ));

        $os = $lista[0] ?? null;
        if ($os === null) {
            return $this->presenter->error($response, 'Ordem de Serviço não encontrada para este cliente e veículo.', HttpStatusCodeEnum::NotFound);
        }

        $output = new ObterOrdemServicoOutputDTO(
            ordemServico: $os,
            pecas: $this->itensGateway->buscarPecasPorOrdemServico($os->id()),
            servicos: $this->itensGateway->buscarServicosPorOrdemServico($os->id()),
        );

        return $this->presenter->success($response, OrdemServicoCompletaResponseDTO::fromOutputDTO($output), HttpStatusCodeEnum::Ok);
    }
}

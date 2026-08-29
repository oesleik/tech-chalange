<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\Controller;

use App\Clientes\Application\UseCase\ListarClientes\ListarClientesInputDTO;
use App\Clientes\Application\UseCase\ListarClientes\ListarClientesUseCaseInterface;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Domain\ValueObject\FiltroOrdemServico;
use App\OrdemServico\Presentation\Http\Controller\ConsultarOrdemServicoPorVeiculoEClienteController;
use App\OrdemServico\Presentation\Http\DTO\OrdemServicoCompletaResponseDTO;
use App\Veiculos\Application\UseCase\ObterVeiculoPorPlaca\ObterVeiculoPorPlacaUseCase;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ConsultarOrdemServicoPorVeiculoEClienteControllerTest extends TestCase {
    private function cliente(): Cliente {
        return Cliente::reconstituir(
            1,
            'Cliente',
            new Cpf('52998224725'),
            new Email('cliente@example.com'),
            new Telefone('5412345678'),
        );
    }

    private function veiculo(): Veiculo {
        return new Veiculo(id: 2, placa: new Placa('ABC1D23'), marca: 'Toyota', modelo: 'Corolla');
    }

    private function os(): OrdemServico {
        return new OrdemServico(3, 1, 2, SituacaoOrdemServicoEnum::EM_EXECUCAO, 0, new DateTime());
    }

    private function criarRequest(array $query): ServerRequestInterface {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn($query);

        return $request;
    }

    public function testConsultaComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequest(['cpf_cnpj' => '52998224725', 'placa' => 'ABC1D23']);

        $clienteUseCase = $this->createMock(ListarClientesUseCaseInterface::class);
        $clienteUseCase->expects($this->once())
            ->method('executar')
            ->with($this->callback(fn(ListarClientesInputDTO $i) => $i->cpfCnpj === '52998224725'))
            ->willReturn([$this->cliente()]);

        $veiculoUseCase = $this->createMock(ObterVeiculoPorPlacaUseCase::class);
        $veiculoUseCase->expects($this->once())->method('executar')->with('ABC1D23')->willReturn($this->veiculo());

        $ordemServicoGateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $ordemServicoGateway->expects($this->once())
            ->method('listar')
            ->with($this->callback(fn(FiltroOrdemServico $f) => $f->idCliente === 1 && $f->idVeiculo === 2 && $f->limit === 1))
            ->willReturn([$this->os()]);

        $itensGateway = $this->createMock(ItensOrdemServicoGatewayInterface::class);
        $itensGateway->method('buscarPecasPorOrdemServico')->willReturn([]);
        $itensGateway->method('buscarServicosPorOrdemServico')->willReturn([]);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('success')
            ->with($response, $this->isInstanceOf(OrdemServicoCompletaResponseDTO::class), HttpStatusCodeEnum::Ok)
            ->willReturn($response);

        $controller = new ConsultarOrdemServicoPorVeiculoEClienteController(
            $clienteUseCase,
            $veiculoUseCase,
            $ordemServicoGateway,
            $itensGateway,
            $presenter,
        );

        $resultado = $controller($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testRetornaBadRequestQuandoCpfCnpjAusente(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequest(['placa' => 'ABC1D23']);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('error')
            ->with($response, 'Os campos cpf_cnpj e placa são obrigatórios.', HttpStatusCodeEnum::BadRequest)
            ->willReturn($response);

        $controller = new ConsultarOrdemServicoPorVeiculoEClienteController(
            $this->createMock(ListarClientesUseCaseInterface::class),
            $this->createMock(ObterVeiculoPorPlacaUseCase::class),
            $this->createMock(OrdemServicoGatewayInterface::class),
            $this->createMock(ItensOrdemServicoGatewayInterface::class),
            $presenter,
        );

        $resultado = $controller($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testRetornaBadRequestQuandoPlacaAusente(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequest(['cpf_cnpj' => '52998224725']);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('error')
            ->with($response, 'Os campos cpf_cnpj e placa são obrigatórios.', HttpStatusCodeEnum::BadRequest)
            ->willReturn($response);

        $controller = new ConsultarOrdemServicoPorVeiculoEClienteController(
            $this->createMock(ListarClientesUseCaseInterface::class),
            $this->createMock(ObterVeiculoPorPlacaUseCase::class),
            $this->createMock(OrdemServicoGatewayInterface::class),
            $this->createMock(ItensOrdemServicoGatewayInterface::class),
            $presenter,
        );

        $resultado = $controller($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testRetornaBadRequestQuandoCpfCnpjInvalido(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequest(['cpf_cnpj' => 'invalido', 'placa' => 'ABC1D23']);

        $clienteUseCase = $this->createMock(ListarClientesUseCaseInterface::class);
        $clienteUseCase->method('executar')->willThrowException(new InvalidArgumentException('CPF/CNPJ inválido.'));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('error')
            ->with($response, 'CPF/CNPJ inválido.', HttpStatusCodeEnum::BadRequest)
            ->willReturn($response);

        $controller = new ConsultarOrdemServicoPorVeiculoEClienteController(
            $clienteUseCase,
            $this->createMock(ObterVeiculoPorPlacaUseCase::class),
            $this->createMock(OrdemServicoGatewayInterface::class),
            $this->createMock(ItensOrdemServicoGatewayInterface::class),
            $presenter,
        );

        $resultado = $controller($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testRetornaNotFoundQuandoClienteNaoEncontrado(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequest(['cpf_cnpj' => '52998224725', 'placa' => 'ABC1D23']);

        $clienteUseCase = $this->createMock(ListarClientesUseCaseInterface::class);
        $clienteUseCase->method('executar')->willReturn([]);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('error')
            ->with($response, 'Cliente não encontrado para o CPF/CNPJ informado.', HttpStatusCodeEnum::NotFound)
            ->willReturn($response);

        $controller = new ConsultarOrdemServicoPorVeiculoEClienteController(
            $clienteUseCase,
            $this->createMock(ObterVeiculoPorPlacaUseCase::class),
            $this->createMock(OrdemServicoGatewayInterface::class),
            $this->createMock(ItensOrdemServicoGatewayInterface::class),
            $presenter,
        );

        $resultado = $controller($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testRetornaNotFoundQuandoVeiculoNaoEncontrado(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequest(['cpf_cnpj' => '52998224725', 'placa' => 'ABC1D23']);

        $clienteUseCase = $this->createMock(ListarClientesUseCaseInterface::class);
        $clienteUseCase->method('executar')->willReturn([$this->cliente()]);

        $veiculoUseCase = $this->createMock(ObterVeiculoPorPlacaUseCase::class);
        $veiculoUseCase->method('executar')->willThrowException(VeiculoNaoEncontradoException::comPlaca('ABC1D23'));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('error')
            ->with($response, 'Veículo não encontrado para a placa informada.', HttpStatusCodeEnum::NotFound)
            ->willReturn($response);

        $controller = new ConsultarOrdemServicoPorVeiculoEClienteController(
            $clienteUseCase,
            $veiculoUseCase,
            $this->createMock(OrdemServicoGatewayInterface::class),
            $this->createMock(ItensOrdemServicoGatewayInterface::class),
            $presenter,
        );

        $resultado = $controller($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testRetornaNotFoundQuandoOrdemServicoNaoEncontrada(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequest(['cpf_cnpj' => '52998224725', 'placa' => 'ABC1D23']);

        $clienteUseCase = $this->createMock(ListarClientesUseCaseInterface::class);
        $clienteUseCase->method('executar')->willReturn([$this->cliente()]);

        $veiculoUseCase = $this->createMock(ObterVeiculoPorPlacaUseCase::class);
        $veiculoUseCase->method('executar')->willReturn($this->veiculo());

        $ordemServicoGateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $ordemServicoGateway->method('listar')->willReturn([]);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('error')
            ->with($response, 'Ordem de Serviço não encontrada para este cliente e veículo.', HttpStatusCodeEnum::NotFound)
            ->willReturn($response);

        $controller = new ConsultarOrdemServicoPorVeiculoEClienteController(
            $clienteUseCase,
            $veiculoUseCase,
            $ordemServicoGateway,
            $this->createMock(ItensOrdemServicoGatewayInterface::class),
            $presenter,
        );

        $resultado = $controller($request, $response);

        $this->assertSame($response, $resultado);
    }
}

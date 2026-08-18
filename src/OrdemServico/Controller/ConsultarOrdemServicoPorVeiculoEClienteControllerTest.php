<?php

declare(strict_types=1);

use App\Clientes\Application\UseCase\ListarClientes\ListarClientesUseCase;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use App\OrdemServico\Controller\ConsultarOrdemServicoPorVeiculoEClienteController;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\PecaOrdemServicoModel;
use App\OrdemServico\Model\ServicoOrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use App\OrdemServico\Service\ItensOrdemServicoService;
use App\OrdemServico\Service\OrdemServicoService;
use App\Veiculos\Application\UseCase\ObterVeiculoPorPlaca\ObterVeiculoPorPlacaUseCase;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class ConsultarOrdemServicoPorVeiculoEClienteControllerTest extends TestCase {
    public function testConsultarOrdemServico(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $clientesServiceMock = $this->createMock(ListarClientesUseCase::class);
        $obterVeiculoPorPlacaUseCaseMock = $this->createMock(ObterVeiculoPorPlacaUseCase::class);
        $serviceMock = $this->createMock(OrdemServicoService::class);
        $itensServiceMock = $this->createMock(ItensOrdemServicoService::class);

        $controller = new ConsultarOrdemServicoPorVeiculoEClienteController(
            contractResolver: $container->get(ContractResolver::class),
            clienteUseCase: $clientesServiceMock,
            obterVeiculoPorPlacaUseCase: $obterVeiculoPorPlacaUseCaseMock,
            ordemServicoService: $serviceMock,
            itensOrdemServicoService: $itensServiceMock,
        );

        $clientesServiceMock->expects($this->exactly(1))->method("executar")->willReturn([
            new Cliente(
                id: 456,
                nome: "Fulano de Tal",
                cpfCnpj: new Cpf("52998224725"),
                email: new Email("fulano@gmail.com"),
                telefone: new Telefone("54999999999"),
            ),
        ]);

        $obterVeiculoPorPlacaUseCaseMock->expects($this->exactly(1))->method("executar")->willReturn(
            new Veiculo(
                id: 789,
                placa: new Placa("ABC-1234"),
                marca: "Volkswagen",
                modelo: "Gol",
            )
        );

        $serviceMock->expects($this->exactly(1))->method("listarOrdensServico")->willReturn([
            new OrdemServicoModel(
                id: 123,
                idCliente: 456,
                idVeiculo: 789,
                situacao: SituacaoOrdemServicoEnum::EM_DIAGNOSTICO,
                valorTotal: 10.45,
                dataSolicitacao: new DateTime(),
            ),
        ]);

        $itensServiceMock->expects($this->exactly(1))->method("obterPecasPorIdOrdemServico")->with(123)->willReturn([
            new PecaOrdemServicoModel(
                idPeca: 111,
                quantidade: 2,
                valorUnitario: 0,
            ),
        ]);

        $itensServiceMock->expects($this->exactly(1))->method("obterServicosPorIdOrdemServico")->with(123)->willReturn([
            new ServicoOrdemServicoModel(
                idServico: 222,
                quantidade: 1,
                valorUnitario: 0,
            ),
        ]);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

        $request = $request->withQueryParams([
            "cpf_cnpj" => "529.982.247-25",
            "placa" => "ABC-1234",
        ]);

        $response = $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_cliente);
        $this->assertEquals(789, $res->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::EM_DIAGNOSTICO->value, $res->situacao);
        $this->assertEquals(10.45, $res->valor_total);

        $this->assertCount(1, $res->pecas);
        $this->assertCount(1, $res->servicos);

        $this->assertEquals(111, $res->pecas[0]->id_peca);
        $this->assertEquals(2, $res->pecas[0]->quantidade);

        $this->assertEquals(222, $res->servicos[0]->id_servico);
        $this->assertEquals(1, $res->servicos[0]->quantidade);
    }

    public function testConsultarOrdemServicoInvalidInput(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $clientesServiceMock = $this->createMock(ListarClientesUseCase::class);
        $obterVeiculoPorPlacaUseCaseMock = $this->createMock(ObterVeiculoPorPlacaUseCase::class);
        $serviceMock = $this->createMock(OrdemServicoService::class);
        $itensServiceMock = $this->createMock(ItensOrdemServicoService::class);

        $controller = new ConsultarOrdemServicoPorVeiculoEClienteController(
            contractResolver: $container->get(ContractResolver::class),
            clienteUseCase: $clientesServiceMock,
            obterVeiculoPorPlacaUseCase: $obterVeiculoPorPlacaUseCaseMock,
            ordemServicoService: $serviceMock,
            itensOrdemServicoService: $itensServiceMock,
        );

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

        $request = $request->withQueryParams([
            "cpf_cnpj" => "",
            "placa" => "",
        ]);

        $response = $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
        );

        $this->assertEquals($response->getStatusCode(), 400);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());
        $this->assertStringContainsString("cpf_cnpj", $res->errors[0]->field);
    }

    public function testConsultarOrdemServicoClienteNaoEncontrado(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $clientesServiceMock = $this->createMock(ListarClientesUseCase::class);
        $obterVeiculoPorPlacaUseCaseMock = $this->createMock(ObterVeiculoPorPlacaUseCase::class);
        $serviceMock = $this->createMock(OrdemServicoService::class);
        $itensServiceMock = $this->createMock(ItensOrdemServicoService::class);

        $controller = new ConsultarOrdemServicoPorVeiculoEClienteController(
            contractResolver: $container->get(ContractResolver::class),
            clienteUseCase: $clientesServiceMock,
            obterVeiculoPorPlacaUseCase: $obterVeiculoPorPlacaUseCaseMock,
            ordemServicoService: $serviceMock,
            itensOrdemServicoService: $itensServiceMock,
        );

        $clientesServiceMock->expects($this->exactly(1))->method("executar")->willReturn([]);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

        $request = $request->withQueryParams([
            "cpf_cnpj" => "529.982.247-25",
            "placa" => "ABC-1234",
        ]);

        $response = $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
        );

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testConsultarOrdemServicoVeiculoNaoEncontrado(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $clientesServiceMock = $this->createMock(ListarClientesUseCase::class);
        $obterVeiculoPorPlacaUseCaseMock = $this->createMock(ObterVeiculoPorPlacaUseCase::class);
        $serviceMock = $this->createMock(OrdemServicoService::class);
        $itensServiceMock = $this->createMock(ItensOrdemServicoService::class);

        $controller = new ConsultarOrdemServicoPorVeiculoEClienteController(
            contractResolver: $container->get(ContractResolver::class),
            clienteUseCase: $clientesServiceMock,
            obterVeiculoPorPlacaUseCase: $obterVeiculoPorPlacaUseCaseMock,
            ordemServicoService: $serviceMock,
            itensOrdemServicoService: $itensServiceMock,
        );

        $clientesServiceMock->expects($this->exactly(1))->method("executar")->willReturn([
            new Cliente(
                id: 456,
                nome: "Fulano de Tal",
                cpfCnpj: new Cpf("52998224725"),
                email: new Email("fulano@gmail.com"),
                telefone: new Telefone("54999999999"),
            ),
        ]);

        $obterVeiculoPorPlacaUseCaseMock->expects($this->exactly(1))->method("executar")->willThrowException(VeiculoNaoEncontradoException::comPlaca("ABC-1234"));

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

        $request = $request->withQueryParams([
            "cpf_cnpj" => "529.982.247-25",
            "placa" => "ABC-1234",
        ]);

        $response = $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
        );

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testConsultarOrdemServicoNaoEncontrada(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $clientesServiceMock = $this->createMock(ListarClientesUseCase::class);
        $obterVeiculoPorPlacaUseCaseMock = $this->createMock(ObterVeiculoPorPlacaUseCase::class);
        $serviceMock = $this->createMock(OrdemServicoService::class);
        $itensServiceMock = $this->createMock(ItensOrdemServicoService::class);

        $controller = new ConsultarOrdemServicoPorVeiculoEClienteController(
            contractResolver: $container->get(ContractResolver::class),
            clienteUseCase: $clientesServiceMock,
            obterVeiculoPorPlacaUseCase: $obterVeiculoPorPlacaUseCaseMock,
            ordemServicoService: $serviceMock,
            itensOrdemServicoService: $itensServiceMock,
        );

        $clientesServiceMock->expects($this->exactly(1))->method("executar")->willReturn([
            new Cliente(
                id: 456,
                nome: "Fulano de Tal",
                cpfCnpj: new Cpf("52998224725"),
                email: new Email("fulano@gmail.com"),
                telefone: new Telefone("54999999999"),
            ),
        ]);

        $obterVeiculoPorPlacaUseCaseMock->expects($this->exactly(1))->method("executar")->willReturn(
            new Veiculo(
                id: 789,
                placa: new Placa("ABC-1234"),
                marca: "Volkswagen",
                modelo: "Gol",
            )
        );

        $serviceMock->expects($this->exactly(1))->method("listarOrdensServico")->willReturn([]);

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

        $request = $request->withQueryParams([
            "cpf_cnpj" => "529.982.247-25",
            "placa" => "ABC-1234",
        ]);

        $response = $controller->__invoke(
            request: $request,
            response: $container->get(ResponseInterface::class),
        );

        $this->assertEquals($response->getStatusCode(), 404);
    }
}

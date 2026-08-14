<?php

declare(strict_types=1);

use App\Clientes\Model\ClienteModel;
use App\Clientes\Service\ClienteService;
use App\Clientes\ValueObject\CpfValue;
use App\Clientes\ValueObject\EmailValue;
use App\Clientes\ValueObject\TelefoneValue;
use App\Core\ServiceContainerBuilder;
use App\OrdemServico\Controller\EnviarOrcamentoOrdemServicoEmailController;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use App\OrdemServico\Service\EnviarOrcamentoOrdemServicoEmailService;
use App\OrdemServico\Service\OrdemServicoService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class EnviarOrcamentoOrdemServicoEmailControllerTest extends TestCase {
    public function testEnviarOrcamentoOrdemServicoEmailController(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $serviceMock = $this->createMock(EnviarOrcamentoOrdemServicoEmailService::class);
        $ordemServicoServiceMock = $this->createMock(OrdemServicoService::class);
        $clienteServiceMock = $this->createMock(ClienteService::class);

        $ordemServicoServiceMock->expects($this->exactly(1))->method("obterOrdemServicoPorId")->with(123)->willReturn(
            new OrdemServicoModel(
                id: 123,
                idCliente: 456,
                idVeiculo: 789,
                situacao: SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO,
                valorTotal: 10.45,
                dataSolicitacao: new DateTime(),
            )
        );

        $clienteServiceMock->expects($this->exactly(1))->method("obterClientePorId")->with(456)->willReturn(
            new ClienteModel(
                id: 456,
                nome: "Fulano de Tal",
                cpfCnpj: new CpfValue("52998224725"),
                email: new EmailValue("fulano@gmail.com"),
                telefone: new TelefoneValue("54999999999")
            )
        );

        $serviceMock->expects($this->exactly(1))->method("enviar");

        $controller = new EnviarOrcamentoOrdemServicoEmailController(
            service: $serviceMock,
            ordemServicoService: $ordemServicoServiceMock,
            clienteService: $clienteServiceMock,
        );

        $response = $controller->__invoke(
            id: 123,
            response: $container->get(ResponseInterface::class)
        );

        $this->assertEquals($response->getStatusCode(), 200);
    }

    public function testEnviarOrcamentoOrdemServicoNaoEncontrada(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $serviceMock = $this->createMock(EnviarOrcamentoOrdemServicoEmailService::class);
        $ordemServicoServiceMock = $this->createMock(OrdemServicoService::class);
        $clienteServiceMock = $this->createMock(ClienteService::class);

        $ordemServicoServiceMock->expects($this->exactly(1))->method("obterOrdemServicoPorId")->with(123)->willReturn(null);

        $controller = new EnviarOrcamentoOrdemServicoEmailController(
            service: $serviceMock,
            ordemServicoService: $ordemServicoServiceMock,
            clienteService: $clienteServiceMock,
        );

        $response = $controller->__invoke(
            id: 123,
            response: $container->get(ResponseInterface::class)
        );

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testEnviarOrcamentoOrdemServicoClienteNaoEncontrado(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $serviceMock = $this->createMock(EnviarOrcamentoOrdemServicoEmailService::class);
        $ordemServicoServiceMock = $this->createMock(OrdemServicoService::class);
        $clienteServiceMock = $this->createMock(ClienteService::class);

        $ordemServicoServiceMock->expects($this->exactly(1))->method("obterOrdemServicoPorId")->with(123)->willReturn(
            new OrdemServicoModel(
                id: 123,
                idCliente: 456,
                idVeiculo: 789,
                situacao: SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO,
                valorTotal: 10.45,
                dataSolicitacao: new DateTime(),
            )
        );

        $clienteServiceMock->expects($this->exactly(1))->method("obterClientePorId")->with(456)->willReturn(null);

        $controller = new EnviarOrcamentoOrdemServicoEmailController(
            service: $serviceMock,
            ordemServicoService: $ordemServicoServiceMock,
            clienteService: $clienteServiceMock,
        );

        $response = $controller->__invoke(
            id: 123,
            response: $container->get(ResponseInterface::class)
        );

        $this->assertEquals($response->getStatusCode(), 404);
    }
}

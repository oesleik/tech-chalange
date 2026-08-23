<?php

declare(strict_types=1);

use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\ValueObject\Cnpj;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use App\Core\Auth\OrdemServico\JwtOrdemServicoService;
use App\Core\Config\AppConfig;
use App\Core\Email\EmailService;
use App\Core\ServiceContainerBuilder;
use App\OrdemServico\Application\Gateway\ItensOrdemServicoGatewayInterface;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Entity\PecaOrdemServico;
use App\OrdemServico\Domain\Entity\ServicoOrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Infrastructure\Email\EnviarOrcamentoEmailGateway;
use PHPUnit\Framework\TestCase;

class EnviarOrcamentoEmailGatewayTest extends TestCase {
    public function testEnviarEmail(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();
        $itensGatewayMock = $this->createMock(ItensOrdemServicoGatewayInterface::class);

        $itensGatewayMock->expects($this->exactly(2))->method("buscarPecasPorOrdemServico")->with(123)->willReturnOnConsecutiveCalls([
            new PecaOrdemServico(
                idPeca: 111,
                quantidade: 10,
                valorUnitario: 80.90,
            ),
        ], []);

        $itensGatewayMock->expects($this->exactly(2))->method("buscarServicosPorOrdemServico")->with(123)->willReturnOnConsecutiveCalls([
            new ServicoOrdemServico(
                idServico: 222,
                quantidade: 2,
                valorUnitario: 50.55,
            ),
        ], []);


        $emailServiceMock = $this->createMock(EmailService::class);
        $emailServiceMock->expects($this->exactly(2))->method("send")->willReturn(true);

        $service = new EnviarOrcamentoEmailGateway(
            itensOrdemServicoGateway: $itensGatewayMock,
            jwtOrdemServicoService: $container->get(JwtOrdemServicoService::class),
            emailService: $emailServiceMock,
            appConfig: $container->get(AppConfig::class),
        );

        $ordemServico = new OrdemServico(
            id: 123,
            idCliente: 456,
            idVeiculo: 789,
            situacao: SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO,
            valorTotal: 45.85,
            dataSolicitacao: new DateTime(),
        );

        $cliente = Cliente::reconstituir(
            id: 456,
            nome: "Fulano de Tal",
            cpfCnpj: new Cnpj("11222333000181"),
            email: new Email("fulano@gmail.com"),
            telefone: new Telefone("54999999988"),
        );

        $service->enviar($ordemServico, $cliente);

        // Teste novamente, sem itens / serviços
        $service->enviar($ordemServico, $cliente);
    }
}

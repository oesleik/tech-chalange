<?php

declare(strict_types=1);

use App\Clientes\Model\ClienteModel;
use App\Clientes\ValueObject\CnpjValue;
use App\Clientes\ValueObject\EmailValue;
use App\Clientes\ValueObject\TelefoneValue;
use App\Core\Auth\OrdemServico\JwtOrdemServicoService;
use App\Core\Config\AppConfig;
use App\Core\Email\EmailService;
use App\Core\ServiceContainerBuilder;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\PecaOrdemServicoModel;
use App\OrdemServico\Model\ServicoOrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use App\OrdemServico\Service\EnviarOrcamentoOrdemServicoEmailService;
use App\OrdemServico\Service\ItensOrdemServicoService;
use PHPUnit\Framework\TestCase;

class EnviarOrcamentoOrdemServicoEmailServiceTest extends TestCase {
    public function testEnviarEmail(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();
        $itensServiceMock = $this->createMock(ItensOrdemServicoService::class);

        $itensServiceMock->expects($this->exactly(2))->method("obterPecasPorIdOrdemServico")->with(123)->willReturnOnConsecutiveCalls([
            new PecaOrdemServicoModel(
                idPeca: 111,
                quantidade: 10,
                valorUnitario: 80.90,
            ),
        ], []);

        $itensServiceMock->expects($this->exactly(2))->method("obterServicosPorIdOrdemServico")->with(123)->willReturnOnConsecutiveCalls([
            new ServicoOrdemServicoModel(
                idServico: 222,
                quantidade: 2,
                valorUnitario: 50.55,
            ),
        ], []);


        $emailServiceMock = $this->createMock(EmailService::class);
        $emailServiceMock->expects($this->exactly(2))->method("send")->willReturn(true);

        $service = new EnviarOrcamentoOrdemServicoEmailService(
            itensOrdemServicoService: $itensServiceMock,
            jwtOrdemServicoService: $container->get(JwtOrdemServicoService::class),
            emailService: $emailServiceMock,
            appConfig: $container->get(AppConfig::class),
        );

        $ordemServico = new OrdemServicoModel(
            id: 123,
            idCliente: 456,
            idVeiculo: 789,
            situacao: SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO,
            valorTotal: 45.85,
            dataSolicitacao: new DateTime(),
        );

        $cliente = new ClienteModel(
            id: 456,
            nome: "Fulano de Tal",
            cpfCnpj: new CnpjValue("AB345678000A91"),
            email: new EmailValue("fulano@gmail.com"),
            telefone: new TelefoneValue("54999999988"),
        );

        $service->enviar($ordemServico, $cliente);

        // Teste novamente, sem itens / serviços
        $service->enviar($ordemServico, $cliente);
    }
}

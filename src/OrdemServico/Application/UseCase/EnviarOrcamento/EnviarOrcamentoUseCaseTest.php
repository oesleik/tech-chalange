<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Application\UseCase;

use App\Clientes\Application\UseCase\ObterCliente\ObterClienteUseCaseInterface;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\Exception\ClienteNaoEncontradoException;
use App\Clientes\Domain\ValueObject\Cnpj;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use App\OrdemServico\Application\Gateway\EnviarOrcamentoGatewayInterface;
use App\OrdemServico\Application\Gateway\OrdemServicoGatewayInterface;
use App\OrdemServico\Application\UseCase\EnviarOrcamento\EnviarOrcamentoUseCase;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use DateTime;
use PHPUnit\Framework\TestCase;

final class EnviarOrcamentoUseCaseTest extends TestCase {
    public function testEnviaOrcamentoQuandoOrdemEClienteExistem(): void {
        $ordemServico = new OrdemServico(123, 456, 789, SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO, 45.85, new DateTime());
        $cliente = Cliente::reconstituir(
            id: 456,
            nome: 'Fulano de Tal',
            cpfCnpj: new Cnpj('11222333000181'),
            email: new Email('fulano@gmail.com'),
            telefone: new Telefone('54999999988'),
        );

        $ordemServicoGateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $ordemServicoGateway->expects($this->once())->method('buscarPorId')->with(123)->willReturn($ordemServico);

        $obterCliente = $this->createMock(ObterClienteUseCaseInterface::class);
        $obterCliente->expects($this->once())->method('executar')->with(456)->willReturn($cliente);

        $enviarOrcamentoGateway = $this->createMock(EnviarOrcamentoGatewayInterface::class);
        $enviarOrcamentoGateway->expects($this->once())->method('enviar')->with($ordemServico, $cliente);

        new EnviarOrcamentoUseCase($ordemServicoGateway, $obterCliente, $enviarOrcamentoGateway)->executar(123);
    }

    public function testLancaExcecaoQuandoOrdemNaoEncontrada(): void {
        $ordemServicoGateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $ordemServicoGateway->method('buscarPorId')->willReturn(null);

        $obterCliente = $this->createMock(ObterClienteUseCaseInterface::class);
        $obterCliente->expects($this->never())->method('executar');

        $enviarOrcamentoGateway = $this->createMock(EnviarOrcamentoGatewayInterface::class);
        $enviarOrcamentoGateway->expects($this->never())->method('enviar');

        $this->expectException(OrdemServicoNaoEncontradaException::class);
        new EnviarOrcamentoUseCase($ordemServicoGateway, $obterCliente, $enviarOrcamentoGateway)->executar(123);
    }

    public function testLancaExcecaoQuandoClienteNaoEncontrado(): void {
        $ordemServico = new OrdemServico(123, 456, 789, SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO, 45.85, new DateTime());

        $ordemServicoGateway = $this->createMock(OrdemServicoGatewayInterface::class);
        $ordemServicoGateway->method('buscarPorId')->willReturn($ordemServico);

        $obterCliente = $this->createMock(ObterClienteUseCaseInterface::class);
        $obterCliente->method('executar')->willThrowException(ClienteNaoEncontradoException::comId(456));

        $enviarOrcamentoGateway = $this->createMock(EnviarOrcamentoGatewayInterface::class);
        $enviarOrcamentoGateway->expects($this->never())->method('enviar');

        $this->expectException(ClienteNaoEncontradoException::class);
        new EnviarOrcamentoUseCase($ordemServicoGateway, $obterCliente, $enviarOrcamentoGateway)->executar(123);
    }
}

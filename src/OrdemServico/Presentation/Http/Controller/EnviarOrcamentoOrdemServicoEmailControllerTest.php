<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\Controller;

use App\Clientes\Domain\Exception\ClienteNaoEncontradoException;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\OrdemServico\Application\UseCase\EnviarOrcamento\EnviarOrcamentoUseCaseInterface;
use App\OrdemServico\Domain\Exception\OrdemServicoNaoEncontradaException;
use App\OrdemServico\Presentation\Http\Controller\EnviarOrcamentoOrdemServicoEmailController;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class EnviarOrcamentoOrdemServicoEmailControllerTest extends TestCase {
    public function testEnviaOrcamentoComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(EnviarOrcamentoUseCaseInterface::class);
        $useCase->expects($this->once())->method('executar')->with(1);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('success')
            ->with(
                $response,
                $this->callback(fn(object $dados) => $dados->mensagem === 'Orçamento da Ordem de Serviço #1 enviado com sucesso.'),
                HttpStatusCodeEnum::Ok,
            )
            ->willReturn($response);

        $controller = new EnviarOrcamentoOrdemServicoEmailController($useCase, $presenter);

        $resultado = $controller(1, $response);

        $this->assertSame($response, $resultado);
    }

    public function testRetornaNotFoundQuandoOrdemServicoNaoEncontrada(): void {
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(EnviarOrcamentoUseCaseInterface::class);
        $useCase->method('executar')->willThrowException(OrdemServicoNaoEncontradaException::comId(1));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('error')
            ->with($response, $this->isType('string'), HttpStatusCodeEnum::NotFound)
            ->willReturn($response);

        $controller = new EnviarOrcamentoOrdemServicoEmailController($useCase, $presenter);

        $resultado = $controller(1, $response);

        $this->assertSame($response, $resultado);
    }

    public function testRetornaNotFoundQuandoClienteNaoEncontrado(): void {
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(EnviarOrcamentoUseCaseInterface::class);
        $useCase->method('executar')->willThrowException(ClienteNaoEncontradoException::comId(9));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())
            ->method('error')
            ->with($response, 'Cliente com id 9 não encontrado.', HttpStatusCodeEnum::NotFound)
            ->willReturn($response);

        $controller = new EnviarOrcamentoOrdemServicoEmailController($useCase, $presenter);

        $resultado = $controller(1, $response);

        $this->assertSame($response, $resultado);
    }
}

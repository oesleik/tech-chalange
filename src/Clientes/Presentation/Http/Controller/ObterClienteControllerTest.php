<?php

declare(strict_types=1);

namespace Tests\Clientes\Presentation\Http\Controller;

use App\Clientes\Application\UseCase\ObterCliente\ObterClienteUseCaseInterface;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\Exception\ClienteNaoEncontradoException;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use App\Clientes\Presentation\Http\Controller\ObterClienteController;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ObterClienteControllerTest extends TestCase {
    public function testExecuteComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);
        $useCase = $this->createMock(ObterClienteUseCaseInterface::class);
        $useCase->expects($this->once())->method('executar')->with(1)->willReturn($this->cliente());
        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('success')->with($response, $this->isInstanceOf(\App\Clientes\Presentation\Http\DTO\ClienteResponseDTO::class), HttpStatusCodeEnum::Ok)->willReturn($response);

        $this->assertSame($response, new ObterClienteController($useCase, $presenter)->execute(1, $response));
    }

    public function testExecuteQuandoNaoEncontrado(): void {
        $response = $this->createMock(ResponseInterface::class);
        $useCase = $this->createMock(ObterClienteUseCaseInterface::class);
        $useCase->method('executar')->willThrowException(new ClienteNaoEncontradoException('Cliente não encontrado'));
        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('error')->with($response, 'Cliente não encontrado', HttpStatusCodeEnum::NotFound)->willReturn($response);

        $this->assertSame($response, new ObterClienteController($useCase, $presenter)->execute(1, $response));
    }

    private function cliente(): Cliente {
        return Cliente::reconstituir(1, 'Cliente', new Cpf('52998224725'), new Email('cliente@example.com'), new Telefone('5412345678'));
    }
}

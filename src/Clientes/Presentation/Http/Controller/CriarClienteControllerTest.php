<?php

declare(strict_types=1);

namespace Tests\Clientes\Presentation\Http\Controller;

use App\Clientes\Application\UseCase\CriarCliente\CriarClienteInputDTO;
use App\Clientes\Application\UseCase\CriarCliente\CriarClienteUseCaseInterface;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\Exception\ClienteJaCadastradoException;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use App\Clientes\Presentation\Http\Controller\CriarClienteController;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class CriarClienteControllerTest extends TestCase {
    public function testExecuteComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->request(['nome' => 'Cliente', 'cpf_cnpj' => '52998224725', 'email' => 'cliente@example.com', 'telefone' => '5412345678']);
        $useCase = $this->createMock(CriarClienteUseCaseInterface::class);
        $useCase->expects($this->once())->method('executar')->with($this->isInstanceOf(CriarClienteInputDTO::class))->willReturn($this->cliente());
        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('success')->with($response, $this->isInstanceOf(\App\Clientes\Presentation\Http\DTO\ClienteResponseDTO::class), HttpStatusCodeEnum::Created)->willReturn($response);

        $resultado = new CriarClienteController($useCase, $presenter)->execute($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoClienteDuplicado(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->request(['nome' => 'Cliente', 'cpf_cnpj' => '52998224725', 'email' => 'cliente@example.com', 'telefone' => '5412345678']);
        $useCase = $this->createMock(CriarClienteUseCaseInterface::class);
        $useCase->method('executar')->willThrowException(new ClienteJaCadastradoException('Cliente duplicado'));
        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('error')->with($response, 'Cliente duplicado', HttpStatusCodeEnum::Conflict)->willReturn($response);

        $this->assertSame($response, new CriarClienteController($useCase, $presenter)->execute($request, $response));
    }

    public function testExecuteQuandoPayloadInvalido(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->request([]);
        $useCase = $this->createMock(CriarClienteUseCaseInterface::class);
        $useCase->expects($this->never())->method('executar');
        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('error')->with($response, 'Nome é obrigatório.', HttpStatusCodeEnum::BadRequest)->willReturn($response);

        $this->assertSame($response, new CriarClienteController($useCase, $presenter)->execute($request, $response));
    }

    private function request(array $payload): ServerRequestInterface {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($payload));
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($stream);
        return $request;
    }

    private function cliente(): Cliente {
        return Cliente::reconstituir(1, 'Cliente', new Cpf('52998224725'), new Email('cliente@example.com'), new Telefone('5412345678'));
    }
}

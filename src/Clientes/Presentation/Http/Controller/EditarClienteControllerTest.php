<?php

declare(strict_types=1);

namespace Tests\Clientes\Presentation\Http\Controller;

use App\Clientes\Application\UseCase\EditarCliente\EditarClienteInputDTO;
use App\Clientes\Application\UseCase\EditarCliente\EditarClienteUseCaseInterface;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\Exception\ClienteJaCadastradoException;
use App\Clientes\Domain\Exception\ClienteNaoEncontradoException;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use App\Clientes\Presentation\Http\Controller\EditarClienteController;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class EditarClienteControllerTest extends TestCase {
    public function testExecuteComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->request(['nome' => 'Novo nome']);
        $useCase = $this->createMock(EditarClienteUseCaseInterface::class);
        $useCase->expects($this->once())->method('executar')->with(1, $this->isInstanceOf(EditarClienteInputDTO::class))->willReturn($this->cliente());
        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('success')->with($response, $this->isInstanceOf(\App\Clientes\Presentation\Http\DTO\ClienteResponseDTO::class), HttpStatusCodeEnum::Ok)->willReturn($response);

        $this->assertSame($response, new EditarClienteController($useCase, $presenter)->execute(1, $request, $response));
    }

    /** @dataProvider excecoesProvider */
    public function testExecuteMapeiaExcecoes(\Throwable $exception, HttpStatusCodeEnum $status): void {
        $response = $this->createMock(ResponseInterface::class);
        $useCase = $this->createMock(EditarClienteUseCaseInterface::class);
        $useCase->method('executar')->willThrowException($exception);
        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('error')->with($response, $exception->getMessage(), $status)->willReturn($response);

        $this->assertSame($response, new EditarClienteController($useCase, $presenter)->execute(1, $this->request([]), $response));
    }

    public static function excecoesProvider(): array {
        return [
            'not found' => [new ClienteNaoEncontradoException('Cliente não encontrado'), HttpStatusCodeEnum::NotFound],
            'conflict' => [new ClienteJaCadastradoException('Cliente duplicado'), HttpStatusCodeEnum::Conflict],
        ];
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

<?php

declare(strict_types=1);

namespace Tests\Clientes\Presentation\Http\Controller;

use App\Clientes\Application\UseCase\ListarClientes\ListarClientesInputDTO;
use App\Clientes\Application\UseCase\ListarClientes\ListarClientesUseCaseInterface;
use App\Clientes\Domain\Entity\Cliente;
use App\Clientes\Domain\ValueObject\Cpf;
use App\Clientes\Domain\ValueObject\Email;
use App\Clientes\Domain\ValueObject\Telefone;
use App\Clientes\Presentation\Http\Controller\ListarClientesController;
use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ListarClientesControllerTest extends TestCase {
    public function testExecuteListaClientes(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([]);
        $useCase = $this->createMock(ListarClientesUseCaseInterface::class);
        $useCase->expects($this->once())->method('executar')->with($this->isInstanceOf(ListarClientesInputDTO::class))->willReturn([$this->cliente()]);
        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('success')->with($response, $this->isInstanceOf(\App\Clientes\Presentation\Http\DTO\ListagemClientesResponseDTO::class), HttpStatusCodeEnum::Ok)->willReturn($response);

        $this->assertSame($response, new ListarClientesController($useCase, $presenter)->execute($request, $response));
    }

    public function testExecuteRetornaErroParaFiltroInvalido(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['cpf_cnpj' => 123]);
        $useCase = $this->createMock(ListarClientesUseCaseInterface::class);
        $useCase->expects($this->never())->method('executar');
        $presenter = $this->createMock(PresenterInterface::class);
        $presenter->expects($this->once())->method('error')->with($response, 'cpf_cnpj deve ser uma string.', HttpStatusCodeEnum::BadRequest)->willReturn($response);

        $this->assertSame($response, new ListarClientesController($useCase, $presenter)->execute($request, $response));
    }

    private function cliente(): Cliente {
        return Cliente::reconstituir(1, 'Cliente', new Cpf('52998224725'), new Email('cliente@example.com'), new Telefone('5412345678'));
    }
}

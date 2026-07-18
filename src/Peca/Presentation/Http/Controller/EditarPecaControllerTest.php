<?php

declare(strict_types=1);

namespace Tests\Peca\Presentation\Http\Controller;

use App\Peca\Application\UseCase\EditarPeca\EditarPecaInputDTO;
use App\Peca\Application\UseCase\EditarPeca\EditarPecaUseCase;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\Exception\PecaNaoEncontradaException;
use App\Peca\Domain\ValueObject\ValorUnitario;
use App\Peca\Presentation\Http\Controller\EditarPecaController;
use App\Core\Contract\ContractResolver;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class EditarPecaControllerTest extends TestCase {
    private function criarRequestComPayload(array $payload): ServerRequestInterface {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($payload));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($stream);

        return $request;
    }

    private function criarResponseMock(): ResponseInterface {
        $body = $this->createMock(StreamInterface::class);
        $body->method('write');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($body);
        $response->method('withHeader')->willReturnSelf();
        $response->method('withStatus')->willReturnSelf();

        return $response;
    }

    private function contractResolverFake(): ContractResolver {
        $resolver = $this->createMock(ContractResolver::class);
        $resolver->method('toJson')->willReturnCallback(
            fn(object $dto) => json_encode($dto),
        );
        return $resolver;
    }

    public function testExecuteComSucesso(): void {
        $response = $this->criarResponseMock();
        $request = $this->criarRequestComPayload([
            'descricao' => 'Filtro de óleo premium',
        ]);

        $pecaAtualizada = Peca::reconstituir(1, 'Filtro de óleo premium', new ValorUnitario(49.90));

        $useCase = $this->createMock(EditarPecaUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->with(1, $this->callback(
                fn(EditarPecaInputDTO $input) => $input->descricao === 'Filtro de óleo premium'
                    && $input->valorUnitario === null
            ))
            ->willReturn($pecaAtualizada);

        $controller = new EditarPecaController($useCase, $this->contractResolverFake());

        $resultado = $controller->execute(1, $request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoPecaNaoEncontrada(): void {
        $response = $this->criarResponseMock();
        $request = $this->criarRequestComPayload([
            'descricao' => 'Qualquer coisa',
        ]);

        $useCase = $this->createMock(EditarPecaUseCase::class);
        $useCase
            ->method('executar')
            ->willThrowException(PecaNaoEncontradaException::comId(99));

        $controller = new EditarPecaController($useCase, $this->contractResolverFake());

        $resultado = $controller->execute(99, $request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoValorUnitarioInvalido(): void {
        $response = $this->criarResponseMock();
        $request = $this->criarRequestComPayload([
            'valor_unitario' => 'não é número',
        ]);

        $useCase = $this->createMock(EditarPecaUseCase::class);
        $useCase->expects($this->never())->method('executar');

        $controller = new EditarPecaController($useCase, $this->contractResolverFake());

        $resultado = $controller->execute(1, $request, $response);

        $this->assertSame($response, $resultado);
    }
}
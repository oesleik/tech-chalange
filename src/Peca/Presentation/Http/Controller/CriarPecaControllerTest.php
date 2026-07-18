<?php

declare(strict_types=1);

namespace Tests\Peca\Presentation\Http\Controller;

use App\Peca\Application\UseCase\CriarPeca\CriarPecaInputDTO;
use App\Peca\Application\UseCase\CriarPeca\CriarPecaUseCase;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\ValueObject\ValorUnitario;
use App\Peca\Presentation\Http\Controller\CriarPecaController;
use App\Core\Contract\ContractResolver;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class CriarPecaControllerTest extends TestCase {
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
            'descricao' => 'Filtro de óleo',
            'valor_unitario' => 49.90,
        ]);

        $pecaCriada = Peca::reconstituir(10, 'Filtro de óleo', new ValorUnitario(49.90));

        $useCase = $this->createMock(CriarPecaUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->with($this->callback(
                fn(CriarPecaInputDTO $input) => $input->descricao === 'Filtro de óleo'
                    && $input->valorUnitario === 49.90
            ))
            ->willReturn($pecaCriada);

        $controller = new CriarPecaController($useCase, $this->contractResolverFake());

        $resultado = $controller->execute($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoPayloadInvalido(): void {
        $response = $this->criarResponseMock();
        $request = $this->criarRequestComPayload([
            'descricao' => '',
            'valor_unitario' => 49.90,
        ]);

        $useCase = $this->createMock(CriarPecaUseCase::class);
        $useCase->expects($this->never())->method('executar');

        $controller = new CriarPecaController($useCase, $this->contractResolverFake());

        $resultado = $controller->execute($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoValorUnitarioNegativo(): void {
        $response = $this->criarResponseMock();
        $request = $this->criarRequestComPayload([
            'descricao' => 'Filtro de óleo',
            'valor_unitario' => -10,
        ]);

        $useCase = $this->createMock(CriarPecaUseCase::class);
        $useCase->expects($this->never())->method('executar');

        $controller = new CriarPecaController($useCase, $this->contractResolverFake());

        $resultado = $controller->execute($request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoUseCaseLancaExcecao(): void {
        $response = $this->criarResponseMock();
        $request = $this->criarRequestComPayload([
            'descricao' => 'Filtro de óleo',
            'valor_unitario' => 49.90,
        ]);

        $useCase = $this->createMock(CriarPecaUseCase::class);
        $useCase
            ->method('executar')
            ->willThrowException(new InvalidArgumentException('Valor unitário não pode ser negativo.'));

        $controller = new CriarPecaController($useCase, $this->contractResolverFake());

        $resultado = $controller->execute($request, $response);

        $this->assertSame($response, $resultado);
    }
}
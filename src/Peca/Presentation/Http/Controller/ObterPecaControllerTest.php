<?php

declare(strict_types=1);

namespace Tests\Peca\Presentation\Http\Controller;

use App\Peca\Application\UseCase\ObterPeca\ObterPecaUseCase;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\Exception\PecaNaoEncontradaException;
use App\Peca\Domain\ValueObject\ValorUnitario;
use App\Peca\Presentation\Http\Controller\ObterPecaController;
use App\Core\Contract\ContractResolver;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class ObterPecaControllerTest extends TestCase {
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
        $peca = Peca::reconstituir(1, 'Filtro de óleo', new ValorUnitario(49.90));

        $useCase = $this->createMock(ObterPecaUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->with(1)
            ->willReturn($peca);

        $controller = new ObterPecaController($useCase, $this->contractResolverFake());

        $resultado = $controller->execute(1, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoNaoEncontrada(): void {
        $response = $this->criarResponseMock();

        $useCase = $this->createMock(ObterPecaUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->with(99)
            ->willThrowException(PecaNaoEncontradaException::comId(99));

        $controller = new ObterPecaController($useCase, $this->contractResolverFake());

        $resultado = $controller->execute(99, $response);

        $this->assertSame($response, $resultado);
    }
}
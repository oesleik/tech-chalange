<?php

declare(strict_types=1);

namespace Tests\Peca\Presentation\Http\Controller;

use App\Peca\Application\UseCase\ListarPeca\ListarPecaUseCase;
use App\Peca\Domain\Entity\Peca;
use App\Peca\Domain\ValueObject\ValorUnitario;
use App\Peca\Presentation\Http\Controller\ListarPecasController;
use App\Core\Contract\ContractResolver;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class ListarPecasControllerTest extends TestCase {
    private function criarResponseMock(): ResponseInterface {
        $body = $this->createMock(StreamInterface::class);
        $body->method('write');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($body);
        $response->method('withHeader')->willReturnSelf();

        return $response;
    }

    private function contractResolverFake(): ContractResolver {
        $resolver = $this->createMock(ContractResolver::class);
        $resolver->method('toJson')->willReturnCallback(
            fn(object $dto) => json_encode($dto),
        );
        return $resolver;
    }

    public function testExecuteRetornaListaDePecas(): void {
        $response = $this->criarResponseMock();

        $pecas = [
            Peca::reconstituir(1, 'Filtro de óleo', new ValorUnitario(49.90)),
            Peca::reconstituir(2, 'Correia dentada', new ValorUnitario(120.0)),
        ];

        $useCase = $this->createMock(ListarPecaUseCase::class);
        $useCase->expects($this->once())->method('executar')->willReturn($pecas);

        $body = $response->getBody();
        $body->expects($this->once())
            ->method('write')
            ->with($this->callback(function (string $json) {
                $data = json_decode($json, true);
                return count($data['pecas']) === 2
                    && $data['pecas'][0]['descricao'] === 'Filtro de óleo'
                    && $data['pecas'][1]['descricao'] === 'Correia dentada';
            }));

        $controller = new ListarPecasController($useCase, $this->contractResolverFake());

        $resultado = $controller->execute($response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteRetornaListaVazia(): void {
        $response = $this->criarResponseMock();

        $useCase = $this->createMock(ListarPecaUseCase::class);
        $useCase->expects($this->once())->method('executar')->willReturn([]);

        $body = $response->getBody();
        $body->expects($this->once())
            ->method('write')
            ->with($this->callback(fn(string $json) => json_decode($json, true)['pecas'] === []));

        $controller = new ListarPecasController($useCase, $this->contractResolverFake());

        $resultado = $controller->execute($response);

        $this->assertSame($response, $resultado);
    }
}
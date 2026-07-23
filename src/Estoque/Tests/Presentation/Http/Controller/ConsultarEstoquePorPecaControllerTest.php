<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Presentation\Http\Controller;

use App\Core\ServiceContainerBuilder;
use App\Estoque\Application\UseCase\ConsultarEstoquePorPeca\ConsultarEstoquePorPecaOutputDTO;
use App\Estoque\Application\UseCase\ConsultarEstoquePorPeca\ConsultarEstoquePorPecaUseCase;
use App\Estoque\Domain\Exception\PecaNaoEncontradaException;
use App\Estoque\Presentation\Http\Controller\ConsultarEstoquePorPecaController;
use App\Core\Presentation\Http\PresenterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use App\Estoque\Application\UseCase\ConsultarEstoquePorPeca\ConsultarEstoquePorPecaUseCaseInterface;

final class ConsultarEstoquePorPecaControllerTest extends TestCase {
    private ConsultarEstoquePorPecaController $controller;
    private ConsultarEstoquePorPecaUseCaseInterface&MockObject $useCaseMock;
    private ResponseInterface $response;

    protected function setUp(): void {
        $container = new ServiceContainerBuilder()->forTesting()->build();

        $this->useCaseMock = $this->createMock(ConsultarEstoquePorPecaUseCaseInterface::class);

        $this->controller = new ConsultarEstoquePorPecaController(
            useCase: $this->useCaseMock,
            presenter: $container->get(PresenterInterface::class),
        );

        $this->response = $container->get(ResponseInterface::class);
    }

    public function testConsultaEstoqueComSucesso(): void {
        $this->useCaseMock
            ->expects($this->once())
            ->method('executar')
            ->with(1)
            ->willReturn(new ConsultarEstoquePorPecaOutputDTO(pecaId: 1, estoqueAtual: 10));

        // simula o request com o atributo de rota {id} = 1
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('GET', '/estoque/pecas/1');
        $request = $request->withAttribute('id', '1');

        $response = $this->controller->execute($request, $this->response);

        $this->assertSame(200, $response->getStatusCode());

        $response->getBody()->rewind();
        $body = json_decode($response->getBody()->getContents());

        $this->assertSame(1, $body->id_peca);
        $this->assertSame(10, $body->estoque_atual);
    }

    public function testRetorna404QuandoPecaNaoEncontrada(): void {
        $this->useCaseMock
            ->method('executar')
            ->willThrowException(PecaNaoEncontradaException::comId(99));

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('GET', '/estoque/pecas/99');
        $request = $request->withAttribute('id', '99');

        $response = $this->controller->execute($request, $this->response);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRetorna404QuandoIdZero(): void {
        $this->useCaseMock
            ->method('executar')
            ->willThrowException(PecaNaoEncontradaException::comId(0));

        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('GET', '/estoque/pecas/0');
        $request = $request->withAttribute('id', '0');

        $response = $this->controller->execute($request, $this->response);

        $this->assertSame(404, $response->getStatusCode());
    }
}

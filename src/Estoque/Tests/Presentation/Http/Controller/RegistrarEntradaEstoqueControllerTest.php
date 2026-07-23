<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Presentation\Http\Controller;

use App\Core\ServiceContainerBuilder;
use App\Estoque\Application\UseCase\RegistrarEntradaEstoque\RegistrarEntradaEstoqueUseCase;
use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;
use App\Estoque\Domain\Exception\PecaNaoEncontradaException;
use App\Estoque\Presentation\Http\Controller\RegistrarEntradaEstoqueController;
use App\Core\Presentation\Http\PresenterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use App\Estoque\Application\UseCase\RegistrarEntradaEstoque\RegistrarEntradaEstoqueUseCaseInterface;

final class RegistrarEntradaEstoqueControllerTest extends TestCase {
    private RegistrarEntradaEstoqueController $controller;
    private RegistrarEntradaEstoqueUseCaseInterface&MockObject $useCaseMock;
    private ResponseInterface $response;
    private ServerRequestInterface $request;

    protected function setUp(): void {
        $container = new ServiceContainerBuilder()->forTesting()->build();

        $this->useCaseMock = $this->createMock(RegistrarEntradaEstoqueUseCaseInterface::class);

        $this->controller = new RegistrarEntradaEstoqueController(
            useCase: $this->useCaseMock,
            presenter: $container->get(PresenterInterface::class),
        );

        $requestFactory = new ServerRequestFactory();
        $this->request  = $requestFactory->createServerRequest('POST', '/estoque/entrada');
        $this->request->getBody()->write(json_encode(['id_peca' => 1, 'quantidade' => 5]));
        $this->request->getBody()->rewind();

        $this->response = $container->get(ResponseInterface::class);
    }

    public function testRegistraEntradaComSucesso(): void {
        $this->useCaseMock
            ->expects($this->once())
            ->method('executar')
            ->willReturn(LancamentoEstoque::reconstituir(10, 1, 5, TipoLancamentoEnum::ENTRADA));

        $response = $this->controller->execute($this->request, $this->response);

        $this->assertSame(200, $response->getStatusCode());

        $response->getBody()->rewind();
        $body = json_decode($response->getBody()->getContents());

        $this->assertSame(10, $body->id);
        $this->assertSame(1, $body->id_peca);
        $this->assertSame(5, $body->quantidade);
        $this->assertSame('entrada', $body->tipo_lancamento);
    }

    public function testRetorna404QuandoPecaNaoEncontrada(): void {
        $this->useCaseMock
            ->method('executar')
            ->willThrowException(PecaNaoEncontradaException::comId(1));

        $response = $this->controller->execute($this->request, $this->response);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRetorna400QuandoQuantidadeInvalida(): void {
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('POST', '/estoque/entrada');
        // quantidade 0 é inválida
        $request->getBody()->write(json_encode(['id_peca' => 1, 'quantidade' => 0]));
        $request->getBody()->rewind();

        // use case não deve ser chamado se a validação falhar
        $this->useCaseMock->expects($this->never())->method('executar');

        $response = $this->controller->execute($request, $this->response);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRetorna400QuandoIdPecaInvalido(): void {
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('POST', '/estoque/entrada');
        $request->getBody()->write(json_encode(['id_peca' => 0, 'quantidade' => 5]));
        $request->getBody()->rewind();

        $this->useCaseMock->expects($this->never())->method('executar');

        $response = $this->controller->execute($request, $this->response);

        $this->assertSame(400, $response->getStatusCode());
    }
}

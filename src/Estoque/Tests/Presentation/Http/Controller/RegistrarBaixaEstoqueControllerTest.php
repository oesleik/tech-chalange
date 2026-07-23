<?php

declare(strict_types=1);

namespace App\Estoque\Tests\Presentation\Http\Controller;

use App\Core\ServiceContainerBuilder;
use App\Estoque\Application\UseCase\RegistrarBaixaEstoque\RegistrarBaixaEstoqueUseCase;
use App\Estoque\Domain\Entity\LancamentoEstoque;
use App\Estoque\Domain\Enum\TipoLancamentoEnum;
use App\Estoque\Domain\Exception\EstoqueInsuficienteException;
use App\Estoque\Domain\Exception\PecaNaoEncontradaException;
use App\Estoque\Presentation\Http\Controller\RegistrarBaixaEstoqueController;
use App\Core\Presentation\Http\PresenterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use App\Estoque\Application\UseCase\RegistrarBaixaEstoque\RegistrarBaixaEstoqueUseCaseInterface;

final class RegistrarBaixaEstoqueControllerTest extends TestCase
{
    private RegistrarBaixaEstoqueController $controller;
    private RegistrarBaixaEstoqueUseCaseInterface&MockObject $useCaseMock;
    private ResponseInterface $response;
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $container = (new ServiceContainerBuilder())->forTesting()->build();

        $this->useCaseMock = $this->createMock(RegistrarBaixaEstoqueUseCaseInterface::class);

        $this->controller = new RegistrarBaixaEstoqueController(
            useCase: $this->useCaseMock,
            presenter: $container->get(PresenterInterface::class),
        );

        $requestFactory = new ServerRequestFactory();
        $this->request  = $requestFactory->createServerRequest('POST', '/estoque/baixa');
        $this->request->getBody()->write(json_encode(['id_peca' => 1, 'quantidade' => 3]));
        $this->request->getBody()->rewind();

        $this->response = $container->get(ResponseInterface::class);
    }

    public function testRegistraBaixaComSucesso(): void
    {
        $this->useCaseMock
            ->expects($this->once())
            ->method('executar')
            ->willReturn(LancamentoEstoque::reconstituir(20, 1, 3, TipoLancamentoEnum::BAIXA));

        $response = $this->controller->execute($this->request, $this->response);

        $this->assertSame(200, $response->getStatusCode());

        $response->getBody()->rewind();
        $body = json_decode($response->getBody()->getContents());

        $this->assertSame(20, $body->id);
        $this->assertSame(1, $body->id_peca);
        $this->assertSame(3, $body->quantidade);
        $this->assertSame('baixa', $body->tipo_lancamento);
    }

    public function testRetorna404QuandoPecaNaoEncontrada(): void
    {
        $this->useCaseMock
            ->method('executar')
            ->willThrowException(PecaNaoEncontradaException::comId(1));

        $response = $this->controller->execute($this->request, $this->response);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testRetorna422QuandoEstoqueInsuficiente(): void
    {
        // saldo insuficiente é regra de negócio — por isso 422, não 400
        $this->useCaseMock
            ->method('executar')
            ->willThrowException(EstoqueInsuficienteException::para(1, 2, 3));

        $response = $this->controller->execute($this->request, $this->response);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testRetorna400QuandoQuantidadeInvalida(): void
    {
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('POST', '/estoque/baixa');
        $request->getBody()->write(json_encode(['id_peca' => 1, 'quantidade' => 0]));
        $request->getBody()->rewind();

        $this->useCaseMock->expects($this->never())->method('executar');

        $response = $this->controller->execute($request, $this->response);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testRetorna400QuandoIdPecaInvalido(): void
    {
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest('POST', '/estoque/baixa');
        $request->getBody()->write(json_encode(['id_peca' => 0, 'quantidade' => 3]));
        $request->getBody()->rewind();

        $this->useCaseMock->expects($this->never())->method('executar');

        $response = $this->controller->execute($request, $this->response);

        $this->assertSame(400, $response->getStatusCode());
    }
}
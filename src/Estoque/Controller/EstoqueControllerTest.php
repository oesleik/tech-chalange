<?php

declare(strict_types=1);

use App\Servicos\Controller\ObterServicoController;
use App\Servicos\Model\ServicoModel;
use App\Servicos\Service\ServicosService;
use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use App\Estoque\Controller\EstoqueController;
use App\Estoque\Model\EstoquePecaModel;
use App\Estoque\Model\LancamentoEstoqueModel;
use App\Estoque\Model\TipoLancamentoEstoqueEnum;
use App\Estoque\Service\EstoqueInsuficienteException;
use App\Estoque\Service\EstoqueService;
use App\Estoque\Service\PecaNaoEncontradaException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class EstoqueControllerTest extends TestCase {
    private EstoqueController $controller;
    private EstoqueService&MockObject $serviceMock;
    private ResponseInterface $response;
    private ServerRequestInterface $request;

    #[Override]
    public function setUp(): void {
        parent::setUp();

        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();
        $this->serviceMock = $this->createMock(EstoqueService::class);

        $this->controller = new EstoqueController(
            service: $this->serviceMock,
            contractResolver: $container->get(ContractResolver::class),
        );

        $requestFactory = new ServerRequestFactory();
        $this->request = $requestFactory->createServerRequest("POST", "/estoque/entrada");

        $this->request->getBody()->write(json_encode([
            "id_peca" => 456,
            "quantidade" => 10,
        ]));

        $this->request->getBody()->rewind();

        $this->response = $container->get(ResponseInterface::class);
    }

    public function testRegistrarEntrada(): void {
        $this->serviceMock->expects($this->exactly(1))->method("registrarEntrada")->with(456, 10)->willReturn(
            new LancamentoEstoqueModel(
                id: 123,
                idPeca: 456,
                quantidade: 10,
                tipoLancamento: TipoLancamentoEstoqueEnum::ENTRADA
            )
        );

        $response = $this->controller->registrarEntrada(
            request: $this->request,
            response: $this->response,
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_peca);
        $this->assertEquals(10, $res->quantidade);
        $this->assertEquals(TipoLancamentoEstoqueEnum::ENTRADA->value, $res->tipo_lancamento);
    }

    public function testRegistrarEntradaPecaNaoEncontrada(): void {
        $this->serviceMock->expects($this->exactly(1))->method("registrarEntrada")->with(456, 10)->willThrowException(
            new PecaNaoEncontradaException()
        );

        $response = $this->controller->registrarEntrada(
            request: $this->request,
            response: $this->response,
        );

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testRegistrarEntradaInvalidInput(): void {
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/estoque/entrada");

        $request->getBody()->write(json_encode([
            "id_peca" => 0,
            "quantidade" => 0,
        ]));

        $request->getBody()->rewind();

        $response = $this->controller->registrarEntrada(
            request: $request,
            response: $this->response,
        );

        $this->assertEquals($response->getStatusCode(), 400);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());
        $this->assertStringContainsString("id_peca", $res->errors[0]->field);
        $this->assertStringContainsString("quantidade", $res->errors[1]->field);
    }

    public function testRegistrarBaixa(): void {
        $this->serviceMock->expects($this->exactly(1))->method("registrarBaixa")->with(456, 10)->willReturn(
            new LancamentoEstoqueModel(
                id: 123,
                idPeca: 456,
                quantidade: 10,
                tipoLancamento: TipoLancamentoEstoqueEnum::BAIXA
            )
        );

        $response = $this->controller->registrarBaixa(
            request: $this->request,
            response: $this->response,
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_peca);
        $this->assertEquals(10, $res->quantidade);
        $this->assertEquals(TipoLancamentoEstoqueEnum::BAIXA->value, $res->tipo_lancamento);
    }

    public function testRegistrarBaixaPecaNaoEncontrada(): void {
        $this->serviceMock->expects($this->exactly(1))->method("registrarBaixa")->with(456, 10)->willThrowException(
            new PecaNaoEncontradaException()
        );

        $response = $this->controller->registrarBaixa(
            request: $this->request,
            response: $this->response,
        );

        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testRegistrarBaixaEstoqueInsuficiente(): void {
        $this->serviceMock->expects($this->exactly(1))->method("registrarBaixa")->with(456, 10)->willThrowException(
            new EstoqueInsuficienteException()
        );

        $response = $this->controller->registrarBaixa(
            request: $this->request,
            response: $this->response,
        );

        $this->assertEquals($response->getStatusCode(), 422);
    }

    public function testRegistrarBaixaInvalidInput(): void {
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/estoque/baixa");

        $request->getBody()->write(json_encode([
            "id_peca" => 0,
            "quantidade" => 0,
        ]));

        $request->getBody()->rewind();

        $response = $this->controller->registrarBaixa(
            request: $request,
            response: $this->response,
        );

        $this->assertEquals($response->getStatusCode(), 400);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());
        $this->assertStringContainsString("id_peca", $res->errors[0]->field);
        $this->assertStringContainsString("quantidade", $res->errors[1]->field);
    }

    public function testConsultarEstoque(): void {
        $this->serviceMock->expects($this->exactly(1))->method("consultarEstoquePorPeca")->with(123)->willReturn(
            new EstoquePecaModel(
                idPeca: 123,
                estoqueAtual: 10,
            )
        );

        $response = $this->controller->consultarEstoque(
            id: 123,
            response: $this->response,
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id_peca);
        $this->assertEquals(10, $res->estoque_atual);
    }

    public function testConsultarEstoquePecaNaoEncontrada(): void {
        $this->serviceMock->expects($this->exactly(1))->method("consultarEstoquePorPeca")->with(123)->willThrowException(
            new PecaNaoEncontradaException()
        );

        $response = $this->controller->consultarEstoque(
            id: 123,
            response: $this->response,
        );

        $this->assertEquals($response->getStatusCode(), 404);
    }

}

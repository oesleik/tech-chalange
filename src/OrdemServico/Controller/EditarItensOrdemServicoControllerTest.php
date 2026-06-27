<?php

declare(strict_types=1);

use App\Core\Contract\ContractResolver;
use App\Core\Database\DatabaseErrorEnum;
use App\Core\ServiceContainerBuilder;
use App\OrdemServico\Contract\PecaOrdemServicoRequest;
use App\OrdemServico\Controller\EditarItensOrdemServicoController;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\PecaOrdemServicoModel;
use App\OrdemServico\Model\ServicoOrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use App\OrdemServico\Service\ItensOrdemServicoService;
use App\OrdemServico\Service\OrdemServicoService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

class EditarItensOrdemServicoControllerTest extends TestCase {
    private ResponseInterface $response;
	private OrdemServicoService&MockObject $serviceMock;
	private ItensOrdemServicoService&MockObject $itensServiceMock;
	private EditarItensOrdemServicoController $controller;

	protected function setUp(): void {
		parent::setUp();

		$containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

		$this->response = $container->get(ResponseInterface::class);
		$this->serviceMock = $this->createMock(OrdemServicoService::class);
		$this->itensServiceMock = $this->createMock(ItensOrdemServicoService::class);

		$this->controller = new EditarItensOrdemServicoController(
			contractResolver: $container->get(ContractResolver::class),
			ordemServicoService: $this->serviceMock,
			itensService: $this->itensServiceMock,
		);
	}

	/**
	 * @param array{id_peca:int,quantidade:int}[] $pecas
	 * @param array{id_servico:int,quantidade:int}[] $servicos
	 */
	private function addStubMockFor(string $method, float $valorDepois, array $pecas = [], array $servicos = []): void {
		$ordemServico = new OrdemServicoModel(
			id: 123,
			idCliente: 456,
			idVeiculo: 789,
			situacao: SituacaoOrdemServicoEnum::RECEBIDA,
			valorTotal: 10.45,
			dataSolicitacao: new DateTime(),
		);

		$this->serviceMock->expects($this->exactly(2))->method("obterOrdemServicoPorId")->with(123)->willReturnOnConsecutiveCalls(
			$ordemServico,
			$ordemServico->withValorTotal($valorDepois)
		);

		$this->itensServiceMock->expects($this->exactly(1))->method("obterPecasPorIdOrdemServico")->with(123)->willReturn(
			array_map(fn ($v) => new PecaOrdemServicoModel($v["id_peca"], $v["quantidade"], 0), $pecas)
		);

		$this->itensServiceMock->expects($this->exactly(1))->method("obterServicosPorIdOrdemServico")->with(123)->willReturn(
			array_map(fn ($v) => new ServicoOrdemServicoModel($v["id_servico"], $v["quantidade"], 0), $servicos)
		);

		$this->itensServiceMock->expects($this->exactly(1))->method($method);
	}

	public function testAdicionarPecas(): void {
		$requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

		$pecas = [
			[
				"id_peca" => 111,
				"quantidade" => 1,
			],
			[
				"id_peca" => 222,
				"quantidade" => 2,
			],
		];

        $request->getBody()->write(json_encode(["pecas" => $pecas]));
        $request->getBody()->rewind();

		$this->addStubMockFor(method: "adicionarPecas", valorDepois: 123.45, pecas: $pecas);
		$response = $this->controller->adicionarPecas(123, $request, $this->response);

		$this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_cliente);
        $this->assertEquals(789, $res->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::RECEBIDA->value, $res->situacao);
        $this->assertEquals(123.45, $res->valor_total);

        $this->assertCount(2, $res->pecas);
        $this->assertCount(0, $res->servicos);

		$this->assertEquals(111, $res->pecas[0]->id_peca);
		$this->assertEquals(1, $res->pecas[0]->quantidade);
	}

	public function testAtualizarPecas(): void {
		$requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

		$pecas = [
			[
				"id_peca" => 111,
				"quantidade" => 1,
			],
			[
				"id_peca" => 222,
				"quantidade" => 2,
			],
		];

        $request->getBody()->write(json_encode(["pecas" => $pecas]));
        $request->getBody()->rewind();

		$this->addStubMockFor(method: "atualizarPecas", valorDepois: 123.45, pecas: $pecas);
		$response = $this->controller->atualizarPecas(123, $request, $this->response);

		$this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_cliente);
        $this->assertEquals(789, $res->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::RECEBIDA->value, $res->situacao);
        $this->assertEquals(123.45, $res->valor_total);

        $this->assertCount(2, $res->pecas);
        $this->assertCount(0, $res->servicos);

		$this->assertEquals(111, $res->pecas[0]->id_peca);
		$this->assertEquals(1, $res->pecas[0]->quantidade);
	}

	public function testAdicionarServicos(): void {
		$requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

		$servicos = [
			[
				"id_servico" => 111,
				"quantidade" => 1,
			],
			[
				"id_servico" => 222,
				"quantidade" => 2,
			],
		];

        $request->getBody()->write(json_encode(["servicos" => $servicos]));
        $request->getBody()->rewind();

		$this->addStubMockFor(method: "adicionarServicos", valorDepois: 123.45, servicos: $servicos);
		$response = $this->controller->adicionarServicos(123, $request, $this->response);

		$this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_cliente);
        $this->assertEquals(789, $res->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::RECEBIDA->value, $res->situacao);
        $this->assertEquals(123.45, $res->valor_total);

        $this->assertCount(0, $res->pecas);
        $this->assertCount(2, $res->servicos);

		$this->assertEquals(111, $res->servicos[0]->id_servico);
		$this->assertEquals(1, $res->servicos[0]->quantidade);
	}

	public function testAtualizarServicos(): void {
		$requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

		$servicos = [
			[
				"id_servico" => 111,
				"quantidade" => 1,
			],
			[
				"id_servico" => 222,
				"quantidade" => 2,
			],
		];

        $request->getBody()->write(json_encode(["servicos" => $servicos]));
        $request->getBody()->rewind();

		$this->addStubMockFor(method: "atualizarServicos", valorDepois: 123.45, servicos: $servicos);
		$response = $this->controller->atualizarServicos(123, $request, $this->response);

		$this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_cliente);
        $this->assertEquals(789, $res->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::RECEBIDA->value, $res->situacao);
        $this->assertEquals(123.45, $res->valor_total);

        $this->assertCount(0, $res->pecas);
        $this->assertCount(2, $res->servicos);

		$this->assertEquals(111, $res->servicos[0]->id_servico);
		$this->assertEquals(1, $res->servicos[0]->quantidade);
	}

	public function testOrdemServicoNaoEncontrada(): void {
		$requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

		$pecas = [
			[
				"id_peca" => 111,
				"quantidade" => 1,
			],
		];

        $request->getBody()->write(json_encode(["pecas" => $pecas]));
        $request->getBody()->rewind();

		$this->serviceMock->expects($this->exactly(1))->method("obterOrdemServicoPorId")->with(123)->willReturn(null);
		$response = $this->controller->adicionarPecas(123, $request, $this->response);
		$this->assertEquals($response->getStatusCode(), 404);
	}

	public function testOrdemServicoFinalizada(): void {
		$requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

		$pecas = [
			[
				"id_peca" => 111,
				"quantidade" => 1,
			],
		];

        $request->getBody()->write(json_encode(["pecas" => $pecas]));
        $request->getBody()->rewind();

		$this->serviceMock->expects($this->exactly(1))->method("obterOrdemServicoPorId")->with(123)->willReturn(
			new OrdemServicoModel(
				id: 123,
				idCliente: 456,
				idVeiculo: 789,
				situacao: SituacaoOrdemServicoEnum::FINALIZADA,
				valorTotal: 10.45,
				dataSolicitacao: new DateTime(),
			)
		);

		$response = $this->controller->adicionarPecas(123, $request, $this->response);
		$this->assertEquals($response->getStatusCode(), 422);
	}

	public function testPecaNaoEncontrada(): void {
		$requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

		$pecas = [
			[
				"id_peca" => 111,
				"quantidade" => 1,
			],
		];

        $request->getBody()->write(json_encode(["pecas" => $pecas]));
        $request->getBody()->rewind();

		$this->serviceMock->expects($this->exactly(1))->method("obterOrdemServicoPorId")->with(123)->willReturn(
			new OrdemServicoModel(
				id: 123,
				idCliente: 456,
				idVeiculo: 789,
				situacao: SituacaoOrdemServicoEnum::RECEBIDA,
				valorTotal: 10.45,
				dataSolicitacao: new DateTime(),
			)
		);

		$exception = new PDOException();
        $exception->errorInfo = [0, DatabaseErrorEnum::NO_REFERENCED_ROW->value];

		$this->itensServiceMock->expects($this->exactly(1))->method("adicionarPecas")->willThrowException($exception);

		$response = $this->controller->adicionarPecas(123, $request, $this->response);
		$this->assertEquals($response->getStatusCode(), 409);
	}

	public function testDatabaseError(): void {
		$requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

		$pecas = [
			[
				"id_peca" => 111,
				"quantidade" => 1,
			],
		];

        $request->getBody()->write(json_encode(["pecas" => $pecas]));
        $request->getBody()->rewind();

		$this->serviceMock->expects($this->exactly(1))->method("obterOrdemServicoPorId")->with(123)->willReturn(
			new OrdemServicoModel(
				id: 123,
				idCliente: 456,
				idVeiculo: 789,
				situacao: SituacaoOrdemServicoEnum::RECEBIDA,
				valorTotal: 10.45,
				dataSolicitacao: new DateTime(),
			)
		);

		$exception = new PDOException();
		$this->itensServiceMock->expects($this->exactly(1))->method("adicionarPecas")->willThrowException($exception);

		$this->expectException(PDOException::class);
		$this->controller->adicionarPecas(123, $request, $this->response);
	}

	public function testInvalidInput(): void {
		$requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest("POST", "/ordens-servico/");

		$pecas = [
			[
				"id_peca" => -1,
				"quantidade" => -1,
			],
		];

        $request->getBody()->write(json_encode(["pecas" => $pecas]));
        $request->getBody()->rewind();

		$response = $this->controller->adicionarPecas(123, $request, $this->response);
		$this->assertEquals($response->getStatusCode(), 400);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());
        $this->assertStringContainsString("id_peca", $res->errors[0]->field);
        $this->assertStringContainsString("quantidade", $res->errors[1]->field);
	}
}

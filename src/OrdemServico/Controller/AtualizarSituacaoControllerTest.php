<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use App\OrdemServico\Controller\AtualizarSituacaoController;
use App\OrdemServico\Model\OrdemServicoModel;
use App\OrdemServico\Model\SituacaoOrdemServicoEnum;
use App\OrdemServico\Service\OrdemServicoService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class AtualizarSituacaoControllerTest extends TestCase {
    private ResponseInterface $response;
    private OrdemServicoService $service;
    private PDOStatement&Stub $stmtStub;
    private AtualizarSituacaoController $controller;

    protected function setUp(): void {
        parent::setUp();

        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $this->response = $container->get(ResponseInterface::class);
        $this->stmtStub = $this->createStub(PDOStatement::class);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($this->stmtStub);

        $this->service = new OrdemServicoService($dbStub);

        $this->controller = new AtualizarSituacaoController(
            contractResolver: $container->get(ContractResolver::class),
            service: $this->service,
        );
    }

    private function addStubFor(SituacaoOrdemServicoEnum $situacao): void {
        $this->stmtStub->method("fetchObject")->willReturn((object) [
            "id" => "123",
            "id_cliente" => "456",
            "id_veiculo" => "789",
            "situacao" => $situacao->value,
            "valor_total" => "45.85",
            "data_solicitacao" => "2026-06-02 12:45:23",
        ]);
    }

    public function testAtualizarParaEmDiagnostico(): void {
        $this->addStubFor(SituacaoOrdemServicoEnum::RECEBIDA);

        $response = $this->controller->atualizarParaEmDiagnostico(123, $this->response);

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_cliente);
        $this->assertEquals(789, $res->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::EM_DIAGNOSTICO->value, $res->situacao);
        $this->assertEquals(45.85, $res->valor_total);
        $this->assertEquals("2026-06-02 12:45:23", $res->data_solicitacao);
        $this->assertEquals(null, $res->data_aprovacao);
    }

    public function testAtualizarParaAguardandoAprovacao(): void {
        $this->addStubFor(SituacaoOrdemServicoEnum::EM_DIAGNOSTICO);

        $response = $this->controller->atualizarParaAguardandoAprovacao(123, $this->response);

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_cliente);
        $this->assertEquals(789, $res->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO->value, $res->situacao);
        $this->assertEquals(45.85, $res->valor_total);
        $this->assertEquals("2026-06-02 12:45:23", $res->data_solicitacao);
        $this->assertEquals(null, $res->data_aprovacao);
    }

    public function testAtualizarParaEmExecucao(): void {
        $this->addStubFor(SituacaoOrdemServicoEnum::APROVADA);

        $response = $this->controller->atualizarParaEmExecucao(123, $this->response);

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_cliente);
        $this->assertEquals(789, $res->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::EM_EXECUCAO->value, $res->situacao);
        $this->assertEquals(45.85, $res->valor_total);
        $this->assertEquals("2026-06-02 12:45:23", $res->data_solicitacao);
        $this->assertEquals(null, $res->data_aprovacao);
    }

    public function testAtualizarParaFinalizada(): void {
        $this->addStubFor(SituacaoOrdemServicoEnum::EM_EXECUCAO);

        $response = $this->controller->atualizarParaFinalizada(123, $this->response);

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_cliente);
        $this->assertEquals(789, $res->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::FINALIZADA->value, $res->situacao);
        $this->assertEquals(45.85, $res->valor_total);
        $this->assertEquals("2026-06-02 12:45:23", $res->data_solicitacao);
        $this->assertEquals(null, $res->data_aprovacao);
    }

    public function testAtualizarParaEntregue(): void {
        $this->addStubFor(SituacaoOrdemServicoEnum::FINALIZADA);

        $response = $this->controller->atualizarParaEntregue(123, $this->response);

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents());

        $this->assertEquals(123, $res->id);
        $this->assertEquals(456, $res->id_cliente);
        $this->assertEquals(789, $res->id_veiculo);
        $this->assertEquals(SituacaoOrdemServicoEnum::ENTREGUE->value, $res->situacao);
        $this->assertEquals(45.85, $res->valor_total);
        $this->assertEquals("2026-06-02 12:45:23", $res->data_solicitacao);
        $this->assertEquals(null, $res->data_aprovacao);
    }

    public function testAtualizarOrdemServicoNaoEncontrada(): void {
        $this->stmtStub->method("fetchObject")->willReturn(false);
        $response = $this->controller->atualizarParaEntregue(123, $this->response);
        $this->assertEquals($response->getStatusCode(), 404);
    }

    public function testAtualizarSituacaoBloqueada(): void {
        $this->addStubFor(SituacaoOrdemServicoEnum::AGUARDANDO_APROVACAO);
        $response = $this->controller->atualizarParaEmExecucao(123, $this->response);
        $this->assertEquals($response->getStatusCode(), 409);
    }

}

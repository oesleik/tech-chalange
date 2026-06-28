<?php

declare(strict_types=1);

use App\Core\Contract\ContractResolver;
use App\Core\ServiceContainerBuilder;
use App\OrdemServico\Contract\Relatorios\RelatorioMediaTempoServicosResponse;
use App\OrdemServico\Contract\Relatorios\ServicoRelatorioMediaTempoServicosResponse;
use App\OrdemServico\Controller\RelatoriosOrdemServicoController;
use App\OrdemServico\Service\RelatorioMediaTempoServicosService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class RelatoriosOrdemServicoControllerTest extends TestCase {
    public function testRelatorioMediaTempoServicos(): void {
        $containerBuilder = new ServiceContainerBuilder();
        $container = $containerBuilder->forTesting()->build();

        $serviceMock = $this->createMock(RelatorioMediaTempoServicosService::class);
        $serviceMock->expects($this->exactly(1))->method("gerarRelatorio")->willReturn(
            new RelatorioMediaTempoServicosResponse(
                servicos: [
                    new ServicoRelatorioMediaTempoServicosResponse(
                        id_servico: 111,
                        descricao: "Troca de óleo",
                        valor_unitario: 60.80,
                        total_tempo_executando: 10,
                        quantidade_execucoes: 8,
                        min_tempo_execucao: 1,
                        max_tempo_execucao: 2,
                        media_tempo: 1.25
                    ),
                    new ServicoRelatorioMediaTempoServicosResponse(
                        id_servico: 222,
                        descricao: "Balanceamento",
                        valor_unitario: 80,
                        total_tempo_executando: 10,
                        quantidade_execucoes: 5,
                        min_tempo_execucao: 1,
                        max_tempo_execucao: 3,
                        media_tempo: 2
                    ),
                ]
            )
        );

        $controller = new RelatoriosOrdemServicoController();

        $response = $controller->relatorioMediaTempoServicos(
            service: $serviceMock,
            contractResolver: $container->get(ContractResolver::class),
            response: $container->get(ResponseInterface::class),
        );

        $this->assertEquals($response->getStatusCode(), 200);

        $response->getBody()->rewind();
        $res = json_decode($response->getBody()->getContents())->servicos;

        $this->assertEquals(111, $res[0]->id_servico);
        $this->assertEquals("Troca de óleo", $res[0]->descricao);
        $this->assertEquals(60.80, $res[0]->valor_unitario);
        $this->assertEquals(1.25, $res[0]->media_tempo);
        $this->assertEquals(8, $res[0]->quantidade_execucoes);
        $this->assertEquals(10, $res[0]->total_tempo_executando);
        $this->assertEquals(1, $res[0]->min_tempo_execucao);
        $this->assertEquals(2, $res[0]->max_tempo_execucao);

        $this->assertEquals(222, $res[1]->id_servico);
        $this->assertEquals("Balanceamento", $res[1]->descricao);
        $this->assertEquals(80, $res[1]->valor_unitario);
        $this->assertEquals(2, $res[1]->media_tempo);
        $this->assertEquals(5, $res[1]->quantidade_execucoes);
        $this->assertEquals(10, $res[1]->total_tempo_executando);
        $this->assertEquals(1, $res[1]->min_tempo_execucao);
        $this->assertEquals(3, $res[1]->max_tempo_execucao);
    }
}

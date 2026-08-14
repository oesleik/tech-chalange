<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\OrdemServico\Contract\Relatorios\ServicoRelatorioMediaTempoServicosResponse;
use App\OrdemServico\Service\RelatorioMediaTempoServicosService;
use PHPUnit\Framework\TestCase;

class RelatorioMediaTempoServicosServiceTest extends TestCase {
    public function testGerarRelatorio(): void {
        $mocks = [
            (object) [
                "id_servico" => "111",
                "descricao" => "Troca de óleo",
                "valor_unitario" => "60.80",
                "total_tempo_executando" => "10",
                "quantidade_execucoes" => "8",
                "min_tempo_execucao" => "1",
                "max_tempo_execucao" => "2",
            ],
            (object) [
                "id_servico" => "222",
                "descricao" => "Balanceamento",
                "valor_unitario" => "80",
                "total_tempo_executando" => "10",
                "quantidade_execucoes" => "5",
                "min_tempo_execucao" => "1",
                "max_tempo_execucao" => "3",
            ],
        ];

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchObject")->willReturnOnConsecutiveCalls(...[...$mocks, false]);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $service = new RelatorioMediaTempoServicosService($dbStub);
        $res = $service->gerarRelatorio()->servicos;

        $this->assertInstanceOf(ServicoRelatorioMediaTempoServicosResponse::class, $res[0]);
        $this->assertEquals($mocks[0]->id_servico, $res[0]->id_servico);
        $this->assertEquals($mocks[0]->descricao, $res[0]->descricao);
        $this->assertEquals($mocks[0]->valor_unitario, $res[0]->valor_unitario);
        $this->assertEquals(1.25, $res[0]->media_tempo);
        $this->assertEquals($mocks[0]->quantidade_execucoes, $res[0]->quantidade_execucoes);
        $this->assertEquals($mocks[0]->total_tempo_executando, $res[0]->total_tempo_executando);
        $this->assertEquals($mocks[0]->min_tempo_execucao, $res[0]->min_tempo_execucao);
        $this->assertEquals($mocks[0]->max_tempo_execucao, $res[0]->max_tempo_execucao);

        $this->assertInstanceOf(ServicoRelatorioMediaTempoServicosResponse::class, $res[1]);
        $this->assertEquals($mocks[1]->id_servico, $res[1]->id_servico);
        $this->assertEquals($mocks[1]->descricao, $res[1]->descricao);
        $this->assertEquals($mocks[1]->valor_unitario, $res[1]->valor_unitario);
        $this->assertEquals(2, $res[1]->media_tempo);
        $this->assertEquals($mocks[1]->quantidade_execucoes, $res[1]->quantidade_execucoes);
        $this->assertEquals($mocks[1]->total_tempo_executando, $res[1]->total_tempo_executando);
        $this->assertEquals($mocks[1]->min_tempo_execucao, $res[1]->min_tempo_execucao);
        $this->assertEquals($mocks[1]->max_tempo_execucao, $res[1]->max_tempo_execucao);
    }

}

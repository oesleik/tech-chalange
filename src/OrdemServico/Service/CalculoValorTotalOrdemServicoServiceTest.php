<?php

declare(strict_types=1);

use App\Core\AppDatabase;
use App\OrdemServico\Service\CalculoValorTotalOrdemServicoService;
use App\OrdemServico\Service\OrdemServicoService;
use PHPUnit\Framework\TestCase;

class CalculoValorTotalOrdemServicoServiceTest extends TestCase {
    public function testCalcularEAtualizar(): void {
        $pecas = [
            (object) [
                "quantidade" => "1",
                "valor_unitario" => "80.90",
            ],
        ];

        $servicos = [
            (object) [
                "quantidade" => "2",
                "valor_unitario" => "45.52",
            ],
        ];

        $totalEsperado = 171.94;

        $stmtStub = $this->createStub(PDOStatement::class);
        $stmtStub->method("fetchAll")->willReturnOnConsecutiveCalls($pecas, $servicos);

        $dbStub = $this->createStub(AppDatabase::class);
        $dbStub->method("prepare")->willReturn($stmtStub);

        $osMock = $this->createMock(OrdemServicoService::class);
        $osMock->expects($this->exactly(1))->method("atualizarValorTotal")->with(123, $totalEsperado);

        $service = new CalculoValorTotalOrdemServicoService($dbStub, $osMock);
        $service->calcularEAtualizar(123);
    }
}

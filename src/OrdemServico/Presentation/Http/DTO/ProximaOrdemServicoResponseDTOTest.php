<?php

declare(strict_types=1);

namespace Tests\OrdemServico\Presentation\Http\DTO;

use App\Core\Config\AppConfig;
use App\OrdemServico\Application\UseCase\ObterOrdemServico\ObterOrdemServicoOutputDTO;
use App\OrdemServico\Domain\Entity\OrdemServico;
use App\OrdemServico\Domain\Enum\SituacaoOrdemServicoEnum;
use App\OrdemServico\Presentation\Http\DTO\ProximaOrdemServicoResponseDTO;
use DateTime;
use PHPUnit\Framework\TestCase;

final class ProximaOrdemServicoResponseDTOTest extends TestCase {
    public function testRetornaRealizarDiagnosticoQuandoRecebida(): void {
        $output = $this->criarOutput(SituacaoOrdemServicoEnum::RECEBIDA);
        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->method('getBaseUrl')->willReturn('http://localhost/');

        $dto = ProximaOrdemServicoResponseDTO::fromOutputDTO($output, $appConfig);

        $this->assertSame('realizar_diagnostico', $dto->tipo_servico);
        $this->assertNotEmpty($dto->links);
    }

    public function testRetornaExecutarServicosQuandoNaoRecebida(): void {
        $output = $this->criarOutput(SituacaoOrdemServicoEnum::APROVADA);
        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->method('getBaseUrl')->willReturn('http://localhost/');

        $dto = ProximaOrdemServicoResponseDTO::fromOutputDTO($output, $appConfig);

        $this->assertSame('executar_servicos', $dto->tipo_servico);
    }

    private function criarOutput(SituacaoOrdemServicoEnum $situacao): ObterOrdemServicoOutputDTO {
        return new ObterOrdemServicoOutputDTO(
            ordemServico: new OrdemServico(1, 10, 20, $situacao, 0, new DateTime()),
            pecas: [],
            servicos: [],
        );
    }
}

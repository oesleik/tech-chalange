<?php

declare(strict_types=1);

namespace Tests\Servicos\Presentation\Http\Controller;

use App\Core\Presentation\Http\PresenterInterface;
use App\Servicos\Application\UseCase\ListarServicos\ListarServicosUseCase;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use App\Servicos\Presentation\Http\Controller\ListarServicosController;
use App\Servicos\Presentation\Http\DTO\ListarServicosResponseDTO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ListarServicosControllerTest extends TestCase {
    public function testExecuteRetornaListaDeServicos(): void {
        $response = $this->createMock(ResponseInterface::class);

        $servicos = [
            Servico::reconstituir(123, 'Revisão', new ValorUnitario(150.0)),
            Servico::reconstituir(456, 'Diagnóstico', new ValorUnitario(80.0)),
        ];

        $useCase = $this->createMock(ListarServicosUseCase::class);
        $useCase->expects($this->once())->method('executar')->willReturn($servicos);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('success')
            ->with($response, $this->callback(
                fn(ListarServicosResponseDTO $dto) => count($dto->servicos) === 2
                    && $dto->servicos[0]->id === 123
                    && $dto->servicos[0]->descricao === 'Revisão'
                    && $dto->servicos[0]->valor_unitario === 150.0
                    && $dto->servicos[1]->descricao === 'Diagnóstico'
            ))
            ->willReturn($response);

        $controller = new ListarServicosController($useCase, $presenter);

        $this->assertSame($response, $controller->execute($response));
    }

    public function testExecuteRetornaListaVazia(): void {
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(ListarServicosUseCase::class);
        $useCase->expects($this->once())->method('executar')->willReturn([]);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('success')
            ->with($response, $this->callback(
                fn(ListarServicosResponseDTO $dto) => $dto->servicos === []
            ))
            ->willReturn($response);

        $controller = new ListarServicosController($useCase, $presenter);

        $this->assertSame($response, $controller->execute($response));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Servicos\Presentation\Http\Controller;

use App\Core\Presentation\Http\HttpStatusCodeEnum;
use App\Core\Presentation\Http\PresenterInterface;
use App\Servicos\Application\UseCase\ObterServico\ObterServicoUseCase;
use App\Servicos\Domain\Entity\Servico;
use App\Servicos\Domain\Exception\ServicoNaoEncontradoException;
use App\Servicos\Domain\ValueObject\ValorUnitario;
use App\Servicos\Presentation\Http\Controller\ObterServicoController;
use App\Servicos\Presentation\Http\DTO\ServicoResponseDTO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ObterServicoControllerTest extends TestCase {
    public function testExecuteComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);

        $servico = Servico::reconstituir(123, 'Revisão', new ValorUnitario(150.0));

        $useCase = $this->createMock(ObterServicoUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->with(123)
            ->willReturn($servico);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('success')
            ->with($response, $this->isInstanceOf(ServicoResponseDTO::class))
            ->willReturn($response);

        $controller = new ObterServicoController($useCase, $presenter);

        $this->assertSame($response, $controller->execute(123, $response));
    }

    public function testExecuteQuandoServicoNaoEncontrado(): void {
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(ObterServicoUseCase::class);
        $useCase
            ->method('executar')
            ->willThrowException(ServicoNaoEncontradoException::comId(123));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('error')
            ->with($response, 'Serviço com id 123 não encontrado.', HttpStatusCodeEnum::NotFound)
            ->willReturn($response);

        $controller = new ObterServicoController($useCase, $presenter);

        $this->assertSame($response, $controller->execute(123, $response));
    }

    public function testExecuteQuandoEntradaInvalida(): void {
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(ObterServicoUseCase::class);
        $useCase
            ->method('executar')
            ->willThrowException(new InvalidArgumentException('Id inválido'));

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('error')
            ->with($response, 'Id inválido', HttpStatusCodeEnum::BadRequest)
            ->willReturn($response);

        $controller = new ObterServicoController($useCase, $presenter);

        $this->assertSame($response, $controller->execute(123, $response));
    }
}

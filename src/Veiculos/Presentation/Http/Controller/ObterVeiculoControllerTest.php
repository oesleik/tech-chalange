<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculos\Presentation\Http\Controller;

use App\Core\Infrastructure\Presentation\HttpStatusCodeEnum;
use App\Core\Infrastructure\Presentation\PresenterInterface;
use App\Veiculos\Application\UseCase\ObterVeiculo\ObterVeiculoUseCase;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
use App\Veiculos\Presentation\Http\Controller\ObterVeiculoController;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class ObterVeiculoControllerTest extends TestCase {
    public function testExecuteComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);

        $veiculo = new Veiculo(
            id: 1,
            placa: new \App\Veiculos\Domain\Entity\Placa('ABC1234'),
            marca: 'Toyota',
            modelo: 'Corolla',
        );

        $useCase = $this->createMock(ObterVeiculoUseCase::class);

        $useCase
            ->expects($this->once())
            ->method('executar')
            ->with(1)
            ->willReturn($veiculo);

        $presenter = $this->createMock(PresenterInterface::class);

        $presenter
            ->expects($this->once())
            ->method('success')
            ->with(
                $response,
                $this->isInstanceOf(\App\Veiculos\Presentation\Http\DTO\VeiculoResponseDTO::class)
            )
            ->willReturn($response);

        $controller = new ObterVeiculoController(
            $useCase,
            $presenter,
        );

        $resultado = $controller->execute(1, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoNaoEncontrado(): void {
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(ObterVeiculoUseCase::class);

        $useCase
            ->method('executar')
            ->willThrowException(
                new VeiculoNaoEncontradoException('Veículo não encontrado')
            );

        $presenter = $this->createMock(PresenterInterface::class);

        $presenter
            ->expects($this->once())
            ->method('error')
            ->with(
                $response,
                'Veículo não encontrado',
                HttpStatusCodeEnum::NotFound
            )
            ->willReturn($response);

        $controller = new ObterVeiculoController(
            $useCase,
            $presenter,
        );

        $resultado = $controller->execute(1, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoEntradaInvalida(): void {
        $response = $this->createMock(ResponseInterface::class);

        $useCase = $this->createMock(ObterVeiculoUseCase::class);

        $useCase
            ->method('executar')
            ->willThrowException(
                new InvalidArgumentException('Id inválido')
            );

        $presenter = $this->createMock(PresenterInterface::class);

        $presenter
            ->expects($this->once())
            ->method('error')
            ->with(
                $response,
                'Id inválido',
                HttpStatusCodeEnum::BadRequest
            )
            ->willReturn($response);

        $controller = new ObterVeiculoController(
            $useCase,
            $presenter,
        );

        $resultado = $controller->execute(1, $response);

        $this->assertSame($response, $resultado);
    }
}

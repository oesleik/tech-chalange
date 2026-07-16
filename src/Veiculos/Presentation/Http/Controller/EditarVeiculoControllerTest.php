<?php

declare(strict_types=1);

namespace Tests\Unit\Veiculos\Presentation\Http\Controller;

use App\Core\Infrastructure\Presentation\HttpStatusCodeEnum;
use App\Core\Infrastructure\Presentation\PresenterInterface;
use App\Veiculos\Application\UseCase\EditarVeiculo\EditarVeiculoInputDTO;
use App\Veiculos\Application\UseCase\EditarVeiculo\EditarVeiculoUseCase;
use App\Veiculos\Domain\Entity\Placa;
use App\Veiculos\Domain\Entity\Veiculo;
use App\Veiculos\Domain\Exception\VeiculoJaCadastradoException;
use App\Veiculos\Domain\Exception\VeiculoNaoEncontradoException;
use App\Veiculos\Presentation\Http\Controller\EditarVeiculoController;
use App\Veiculos\Presentation\Http\DTO\EditarVeiculoMapper;
use App\Veiculos\Presentation\Http\DTO\VeiculoResponseDTO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class EditarVeiculoControllerTest extends TestCase {
    private function criarRequestComPayload(array $payload): ServerRequestInterface {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($payload));

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getBody')->willReturn($stream);

        return $request;
    }

    public function testExecuteComSucesso(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComPayload([
            'marca' => 'Honda',
        ]);

        $veiculoAtualizado = new Veiculo(
            id: 1,
            placa: new Placa('ABC1234'),
            marca: 'Honda',
            modelo: 'Corolla',
        );

        $useCase = $this->createMock(EditarVeiculoUseCase::class);
        $useCase
            ->expects($this->once())
            ->method('executar')
            ->with(
                1,
                $this->callback(fn(EditarVeiculoInputDTO $input) => $input->marca === 'Honda'
                    && $input->placa === null
                    && $input->modelo === null)
            )
            ->willReturn($veiculoAtualizado);

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('success')
            ->with(
                $response,
                $this->isInstanceOf(VeiculoResponseDTO::class),
            )
            ->willReturn($response);

        $controller = new EditarVeiculoController(
            $useCase,
            new EditarVeiculoMapper(),
            $presenter,
        );

        $resultado = $controller->execute(1, $request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoVeiculoNaoEncontrado(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComPayload(['marca' => 'Honda']);

        $useCase = $this->createMock(EditarVeiculoUseCase::class);
        $useCase
            ->method('executar')
            ->willThrowException(
                VeiculoNaoEncontradoException::comId(1)
            );

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('error')
            ->with(
                $response,
                'Veículo com id 1 não encontrado.',
                HttpStatusCodeEnum::NotFound,
            )
            ->willReturn($response);

        $controller = new EditarVeiculoController(
            $useCase,
            new EditarVeiculoMapper(),
            $presenter,
        );

        $resultado = $controller->execute(1, $request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoPlacaJaCadastrada(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComPayload(['placa' => 'XYZ9876']);

        $useCase = $this->createMock(EditarVeiculoUseCase::class);
        $useCase
            ->method('executar')
            ->willThrowException(
                VeiculoJaCadastradoException::comPlaca('XYZ-9876')
            );

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('error')
            ->with(
                $response,
                'Veículo com placa XYZ-9876 já cadastrado.',
                HttpStatusCodeEnum::Conflict,
            )
            ->willReturn($response);

        $controller = new EditarVeiculoController(
            $useCase,
            new EditarVeiculoMapper(),
            $presenter,
        );

        $resultado = $controller->execute(1, $request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoPayloadInvalido(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComPayload(['marca' => 123]);

        $useCase = $this->createMock(EditarVeiculoUseCase::class);
        $useCase->expects($this->never())->method('executar');

        $presenter = $this->createMock(PresenterInterface::class);
        $presenter
            ->expects($this->once())
            ->method('error')
            ->with(
                $response,
                "Campo 'marca' deve ser uma string.",
                HttpStatusCodeEnum::BadRequest,
            )
            ->willReturn($response);

        $controller = new EditarVeiculoController(
            $useCase,
            new EditarVeiculoMapper(),
            $presenter,
        );

        $resultado = $controller->execute(1, $request, $response);

        $this->assertSame($response, $resultado);
    }

    public function testExecuteQuandoEntradaInvalida(): void {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->criarRequestComPayload(['placa' => 'ABC1234']);

        $useCase = $this->createMock(EditarVeiculoUseCase::class);
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
                HttpStatusCodeEnum::BadRequest,
            )
            ->willReturn($response);

        $controller = new EditarVeiculoController(
            $useCase,
            new EditarVeiculoMapper(),
            $presenter,
        );

        $resultado = $controller->execute(1, $request, $response);

        $this->assertSame($response, $resultado);
    }
}
